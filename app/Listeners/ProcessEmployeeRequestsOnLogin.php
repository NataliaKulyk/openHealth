<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Division;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {
    }

    /**
     * @throws Throwable
     * @throws ConnectionException
     */
    public function handle(EHealthUserLogin $event): void
    {
        try {
            $pendingRequests = $this->employeeRepository->findPendingRequestsForUser($event->user, $event->legalEntity);
            if ($pendingRequests->isEmpty()) {
                return;
            }

            $userParty = $event->user->party;
            if (!$userParty) {
                return;
            }

            $filterParams = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'party_id' => $userParty->uuid,
                'status' => Status::APPROVED->value,
            ];

            $ehealthResponse = EHealth::employee()->getMany($filterParams);
            $employeesFromApi = $ehealthResponse->getData();

            if (empty($employeesFromApi)) {
                return;
            }

            $partyUuids = collect($employeesFromApi)->pluck('party.uuid')->filter()->unique()->all();
            $divisionUuids = collect($employeesFromApi)->pluck('division.uuid')->filter()->unique()->all();
            $partiesMap = Party::whereIn('uuid', $partyUuids)->with('user')->get()->keyBy('uuid');
            $divisionsMap = Division::whereIn('uuid', $divisionUuids)->get()->keyBy('uuid');

            $approvedEmployeesByPosition = collect($employeesFromApi)->groupBy('position');

            $employeesToUpsert = [];
            $requestsToUpdateData = [];
            $revisionIdsToApply = [];

            foreach ($pendingRequests as $request) {
                if (!$approvedEmployeesByPosition->has($request->position)) {
                    continue;
                }

                $approvedData = $approvedEmployeesByPosition->get($request->position)
                    ->firstWhere(function ($employeeFromApi) use ($request) {
                        return $employeeFromApi['start_date'] === $request->start_date->format('Y-m-d')
                            && $employeeFromApi['employee_type'] === $request->employee_type;
                    });

                if (!$approvedData) {
                    continue;
                }

                $employeesToUpsert[] = $this->prepareEmployeeDataForUpsert(
                    $approvedData,
                    $event->legalEntity,
                    $event->user,
                    $partiesMap,
                    $divisionsMap
                );

                $requestsToUpdateData[$request->id] = [
                    'employee_uuid' => $approvedData['id'],
                    'applied_at' => Carbon::parse($approvedData['updated_at'] ?? 'now')->toIso8601String(),
                    'status' => 'APPROVED',
                ];

                if ($request->revision) {
                    $revisionIdsToApply[] = $request->revision->id;
                }
            }

            if (empty($employeesToUpsert)) {
                return;
            }

            DB::transaction(function () use ($employeesToUpsert, $requestsToUpdateData, $revisionIdsToApply) {
                $this->employeeRepository->upsertEmployees($employeesToUpsert);
                $employeeUuids = array_column($employeesToUpsert, 'uuid');
                $uuidToIdMap = $this->employeeRepository->getEmployeeIdsByUuids($employeeUuids);
                foreach ($requestsToUpdateData as &$data) {
                    $data['employee_id'] = $uuidToIdMap[$data['employee_uuid']] ?? null;
                    unset($data['employee_uuid']);
                }
                unset($data);
                $this->employeeRepository->bulkUpdateEmployeeRequests($requestsToUpdateData);
                if (!empty($revisionIdsToApply)) {
                    $this->employeeRepository->bulkApplyRevisions($revisionIdsToApply);
                }
            });

        } catch (Throwable $e) {
            Log::error('Failed to process employee requests on login.', [
                'user_id' => $event->user->id,
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Prepares data for a single employee record for database insertion/update.
     * This logic is now correctly placed within the listener that orchestrates the process.
     *
     * @param array       $ehealthData  Validated and transformed data from the API client.
     * @param LegalEntity $legalEntity
     * @param User        $user
     * @param Collection  $partiesMap
     * @param Collection  $divisionsMap
     * @return array
     */
    private function prepareEmployeeDataForUpsert(
        array $ehealthData,
        LegalEntity $legalEntity,
        User $user,
        Collection $partiesMap,
        Collection $divisionsMap
    ): array {
        $prepared = [
            'uuid' => $ehealthData['id'],
            'status' => $ehealthData['status'],
            'position' => $ehealthData['position'],
            'employee_type' => $ehealthData['employee_type'],
            'start_date' => $ehealthData['start_date'],
            'end_date' => $ehealthData['end_date'],
            'inserted_at' => Carbon::now(),
            'legal_entity_id' => $legalEntity->id,
            'legal_entity_uuid' => $legalEntity->uuid,
            'party_id' => null,
            'user_id' => $user->id,
            'division_id' => null,
        ];

        $partyUuid = $ehealthData['party']['uuid'] ?? null;
        if ($partyUuid && $partiesMap->has($partyUuid)) {
            $party = $partiesMap->get($partyUuid);
            $prepared['party_id'] = $party->id;
            $prepared['user_id'] = $party->user->id ?? $user->id;
        }

        $divisionUuid = $ehealthData['division']['uuid'] ?? null;
        if ($divisionUuid && $divisionsMap->has($divisionUuid)) {
            $prepared['division_id'] = $divisionsMap->get($divisionUuid)->id;
        }

        return $prepared;
    }
}
