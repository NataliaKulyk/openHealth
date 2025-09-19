<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Repositories\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    public function handle(EHealthUserLogin $event): void
    {
        if ($event->isFirstLogin) {
            return;
        }

        $userParty = $event->user?->party;

        if (!$userParty || !$userParty->uuid) {
            return;
        }

        $pendingRequests = Repository::employee()->findPendingRequestsForUser($event->user, $event->legalEntity);
        if ($pendingRequests->isEmpty()) {
            return;
        }

        try {
            $apiFilters = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'status' => Status::APPROVED->value,
                'party_id' => $userParty->uuid
            ];

            $ehealthResponse = EHealth::employee()->getMany($apiFilters);
            $employeesFromApi = $ehealthResponse->validate();

        } catch (Throwable $e) {
            Log::error('Failed to fetch initial list for employee requests processing.', [
                'user_id' => $event->user->id,
                'legal_entity_id' => $event->legalEntity->id,
                'error_message' => $e->getMessage(),
            ]);

            return;
        }

        if (empty($employeesFromApi)) {
            return;
        }

        $apiEmployeesCollection = collect($employeesFromApi);

        foreach ($pendingRequests as $request) {
            try {
                $matchIndex = $apiEmployeesCollection->search(function ($apiEmployee) use ($request) {
                    return $apiEmployee['position'] === $request->position
                        && $apiEmployee['employee_type'] === $request->employee_type;
                });

                if ($matchIndex === false) {
                    continue;
                }

                $approvedData = $apiEmployeesCollection->pull($matchIndex);

                $employeeApiKeys = ['id', 'status', 'position', 'employee_type', 'start_date', 'end_date', 'is_active'];
                $employeeData = array_intersect_key($approvedData, array_flip($employeeApiKeys));

                $employeeData['uuid'] = $employeeData['id'];
                unset($employeeData['id']);
                $employeeData['legal_entity_uuid'] = $approvedData['legal_entity']['id'] ?? null;
                $employeeData['legal_entity_id'] = $request->legal_entity_id;
                $employeeData['party_id'] = $request->party_id;
                $employeeData['user_id'] = $request->user_id;
                $employeeData['division_id'] = $request->division_id;

                $apiPartyData = $approvedData['party'] ?? [];
                $partyApiKeys = ['id', 'first_name', 'last_name', 'second_name', 'birth_date', 'gender', 'no_tax_id', 'tax_id', 'email'];
                $partyData = array_intersect_key($apiPartyData, array_flip($partyApiKeys));
                if (isset($partyData['id'])) {
                    $partyData['uuid'] = $partyData['id'];
                    unset($partyData['id']);
                }

                $documents = $apiPartyData['documents'] ?? [];
                $phones = $apiPartyData['phones'] ?? [];
                $doctorDataKey = strtolower($approvedData['employee_type'] ?? '');
                $doctorData = $approvedData[$doctorDataKey] ?? [];

                DB::transaction(
                    static function () use ($employeeData, $partyData, $documents, $phones, $doctorData, $request) {
                        $employeeModel = Employee::updateOrCreate(
                            ['uuid' => $employeeData['uuid']],
                            $employeeData
                        );

                        Repository::employee()->updateDetails(
                            $employeeModel,
                            $partyData,
                            $documents,
                            $phones,
                            $doctorData['educations'] ?? null,
                            $doctorData['specialities'] ?? null,
                            $doctorData['qualifications'] ?? null,
                            $doctorData['scienceDegree'] ?? null
                        );

                        $user = $employeeModel->user;
                        $roleName = $employeeModel->employee_type;
                        $legalEntityId = $employeeModel->legal_entity_id;

                        if ($user && $roleName && $legalEntityId) {
                            setPermissionsTeamId($legalEntityId);

                            $user->unsetRelation('roles')->unsetRelation('permissions');

                            if (!$user->hasRole($roleName)) {
                                $user->assignRole($roleName);
                            }
                        }

                        $request->status = Status::APPROVED->value;
                        $request->applied_at = now();
                        $request->employee()->associate($employeeModel);
                        $request->save();

                        if ($request->revision()->exists()) {
                            $request->revision->update(['status' => RevisionStatus::APPLIED->value]);
                        }
                    }
                );

            } catch (Throwable $e) {
                Log::error('Failed to process a single pending employee request.', [
                    'employee_request_id' => $request->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
        }
    }
}
