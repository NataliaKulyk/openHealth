<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Repositories\EmployeeRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    /**
     * The EmployeeApi class is no longer injected.
     */
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

            // Since the API does not allow filtering by request IDs, we get all
            // approved employees and will look for an exact match in the code.
            $filterParams = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'party_id' => $userParty->uuid,
                'status' => Status::APPROVED->value,
            ];

            // --- CHANGE: Call the API client via the EHealth facade/service locator as requested ---
            $employeesFromApi = EHealth::employee()->getMany($filterParams);
            if (empty($employeesFromApi)) {
                return;
            }

            // Group API data by position for easier searching.
            // This will avoid a full array scan in the loop.
            $approvedEmployeesByPosition = collect($employeesFromApi)->groupBy('position');

            // --- Step 1: Prepare data without DB queries ---
            $employeesToUpsert = [];
            $requestsToUpdateData = [];
            $revisionIdsToApply = [];

            foreach ($pendingRequests as $request) {
                // If there are no approved employees in the API for the position from our request, skip it.
                if (!$approvedEmployeesByPosition->has($request->position)) {
                    continue;
                }

                // Look for an EXACT match, not just the first one.
                // We are looking for a record in the API where the position, start date, and type match
                // our local request. This ensures we don't pull an old version.
                $approvedData = $approvedEmployeesByPosition->get($request->position)
                    ->firstWhere(function ($employeeFromApi) use ($request) {
                        return $employeeFromApi['start_date'] === $request->start_date->format('Y-m-d')
                            && $employeeFromApi['employee_type'] === $request->employee_type;
                    });

                // If no exact match is found, proceed to the next request.
                if (!$approvedData) {
                    continue;
                }

                // If we are here, our request has been approved. Preparing the data.
                $employeesToUpsert[] = EHealth::employee()::prepareEmployeeDataForDb(
                    $approvedData,
                    $event->legalEntity,
                    $event->user
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

            // --- Step 2: Execute all operations in a single transaction ---
            DB::transaction(function () use ($employeesToUpsert, $requestsToUpdateData, $revisionIdsToApply) {
                // 1. Create/update all employees with a single query.
                $this->employeeRepository->upsertEmployees($employeesToUpsert);

                // 2. Get the IDs of the newly created/updated employees.
                $employeeUuids = array_column($employeesToUpsert, 'uuid');
                $uuidToIdMap = $this->employeeRepository->getEmployeeIdsByUuids($employeeUuids);

                // 3. Supplement the request update data with real employee_ids.
                foreach ($requestsToUpdateData as &$data) {
                    $data['employee_id'] = $uuidToIdMap[$data['employee_uuid']] ?? null;
                    unset($data['employee_uuid']); // Remove the temporary field.
                }
                unset($data);

                // 4. Update all requests with a single query.
                $this->employeeRepository->bulkUpdateEmployeeRequests($requestsToUpdateData);

                // 5. Update all revisions with a single query.
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
}
