<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Classes\eHealth\Api\Employee as EmployeeApi;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Repositories\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseEmployeeListener
{
    /**
     * The main entry point for the listener.
     */
    public function handle(EHealthUserLogin $event): void
    {
        if (!$this->shouldProcess($event)) {
            return;
        }

        try {
            $ehealthEmployees = $this->fetchEmployeesFromApi($event);
        } catch (Throwable $e) {
            Log::error('Failed to fetch employee list from E-Health.', [
                'listener' => static::class,
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        if (empty($ehealthEmployees)) {
            return;
        }

        // Get a simple list of UUIDs that are currently in E-Health
        $ehealthEmployeeUuids = collect($ehealthEmployees)->pluck('uuid')->all();

        // Get a list of UUIDs that already exist in our local database for this user
        $localEmployeeUuids = $event->user->employees()
            ->whereIn('uuid', $ehealthEmployeeUuids)
            ->pluck('uuid')
            ->all();

        // Find the difference - these are the employees we need to create
        $newEmployeeUuids = array_diff($ehealthEmployeeUuids, $localEmployeeUuids);

        if (empty($newEmployeeUuids)) {
            return; // Nothing to create
        }

        // Find the corresponding local requests for the new employees
        $pendingRequests = $this->getPendingRequestsForNewEmployees($event, collect($ehealthEmployees), $newEmployeeUuids);

        foreach ($pendingRequests as $employeeRequest) {
            try {
                // Find the full data for this specific new employee from the API response
                $approvedData = collect($ehealthEmployees)->firstWhere('uuid', $employeeRequest->employee_uuid_from_api);

                if ($approvedData) {
                    $this->createEmployeeFromRequest($employeeRequest, $approvedData, $event->user);
                }
            } catch (Throwable $e) {
                Log::error('Failed to process a new employee from request.', [
                    'listener' => static::class,
                    'employee_request_id' => $employeeRequest->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    /**
     * Each concrete listener must implement its own condition to run.
     */
    abstract protected function shouldProcess(EHealthUserLogin $event): bool;

    /**
     * Each concrete listener must implement its own way of fetching data.
     */
    abstract protected function fetchEmployeesFromApi(EHealthUserLogin $event): array;

    /**
     * Find local EmployeeRequest records that match the new employees found in the API response.
     */
    protected function getPendingRequestsForNewEmployees(EHealthUserLogin $event, Collection $ehealthEmployees, array $newEmployeeUuids): Collection
    {
        $newEmployeesFromApi = $ehealthEmployees->whereIn('uuid', $newEmployeeUuids);

        $pendingRequests = Repository::employee()->findPendingRequestsForUser($event->user, $event->legalEntity);

        // We need to match local requests to the API response by position and type,
        // then attach the final employee UUID to the request object for later use.
        return $pendingRequests->map(function (EmployeeRequest $request) use ($newEmployeesFromApi) {
            $apiMatch = $newEmployeesFromApi->firstWhere(function ($apiEmployee) use ($request) {
                return $apiEmployee['position'] === $request->position
                    && $apiEmployee['employee_type'] === $request->employee_type;
            });

            if ($apiMatch) {
                // Temporarily attach the final UUID to the request model
                $request->employee_uuid_from_api = $apiMatch['uuid'];

                return $request;
            }

            return null;
        })->filter(); // Remove requests that didn't have a match
    }

    /**
     * The single source of truth for creating an employee, using Revision data as the primary source.
     *
     * @throws Throwable
     */
    protected function createEmployeeFromRequest(EmployeeRequest $employeeRequest, array $approvedData): void
    {
        // THE SOURCE OF TRUTH: Data from the revision, which the user signed.
        $revisionData = $employeeRequest->revision->data;

        // Manually build the employee data array from the correct sources
        $employeeData = [
            // Data from the Revision (what user signed)
            'position' => $revisionData['employee_request_data']['position'],
            'employee_type' => $revisionData['employee_request_data']['employee_type'],
            'start_date' => $revisionData['employee_request_data']['start_date'],
            'end_date' => $revisionData['employee_request_data']['end_date'],
            'division_id' => $revisionData['employee_request_data']['division_id'],

            // Data from existing relationships
            'legal_entity_id' => $employeeRequest->legal_entity_id,
            'legal_entity_uuid' => $employeeRequest->legal_entity_uuid,
            'inserted_at' => now(),
            'party_id' => $employeeRequest->party_id,
            'user_id' => $employeeRequest->party->user_id,

            // Final, confirmed data from the live E-Health response
            'uuid' => $approvedData['uuid'],
            'status' => $approvedData['status'],
            'is_active' => $approvedData['is_active'] ?? true,
        ];

        // Party and Doctor data can still be mapped as they operate on correct sub-arrays
        $partyData = EmployeeApi::mapPartyData($revisionData['party'] ?? []);
        $doctorData = $revisionData['doctor'] ?? [];

        DB::transaction(function () use ($employeeData, $partyData, $doctorData, $revisionData, $employeeRequest) {
            $employeeModel = Employee::updateOrCreate(
                ['uuid' => $employeeData['uuid']],
                $employeeData
            );

            Repository::employee()->updateDetails(
                $employeeModel,
                $partyData,
                $revisionData['documents'] ?? [],
                $revisionData['phones'] ?? [],
                $doctorData['educations'] ?? null,
                $doctorData['specialities'] ?? null,
                $doctorData['qualifications'] ?? null,
                $doctorData['scienceDegree'] ?? null
            );

            $this->assignRoleToUser($employeeModel);
            $this->finalizeEmployeeRequest($employeeRequest, $employeeModel);
        });
    }

    /**
     * Assigns the appropriate role to the user if they don't already have it.
     */
    protected function assignRoleToUser(Employee $employeeModel): void
    {
        $user = $employeeModel->user;
        $roleName = $employeeModel->employee_type;
        $legalEntityId = $employeeModel->legal_entity_id;

        if ($user && $roleName && $legalEntityId) {
            setPermissionsTeamId($legalEntityId);
            $user->unsetRelation('roles');

            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
        }
    }

    /**
     * Updates the status of the EmployeeRequest and its revision.
     */
    protected function finalizeEmployeeRequest(EmployeeRequest $employeeRequest, Employee $employeeModel): void
    {
        // Use a direct update to bypass any potential model observers that might be blocking the save.
        // This is a more direct and robust way to set the final state of the request.
        EmployeeRequest::where('id', $employeeRequest->id)->update(
            [
                'status' => Status::APPROVED->value,
                'applied_at' => now(),
                'employee_id' => $employeeModel->id,
            ]
        );

        if ($employeeRequest->revision()->exists()) {
            $employeeRequest->revision->update(['status' => RevisionStatus::APPLIED->value]);
        }
    }
}
