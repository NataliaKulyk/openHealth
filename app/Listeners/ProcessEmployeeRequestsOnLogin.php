<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RevisionStatus;
use App\Enums\Status;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Relations\Party;
use App\Models\User;
use App\Repositories\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class ProcessEmployeeRequestsOnLogin
{
    /**
     * Handle the user login event to process pending employee requests.
     */
    public function handle(EHealthUserLogin $event): void
    {
        if ($event->isFirstLogin) {
            return;
        }

        $userParty = $event->user?->party;
        if (!$userParty) {
            return;
        }

        $pendingRequests = Repository::employee()->findPendingRequestsForUser($event->user, $event->legalEntity);
        if ($pendingRequests->isEmpty()) {
            return;
        }

        try {
            $employeesFromApi = $this->fetchEmployeesFromApi($event, $userParty);
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

        foreach ($pendingRequests as $employeeRequest) {
            try {
                $this->processPendingRequest($employeeRequest, $apiEmployeesCollection, $event->user);
            } catch (Throwable $e) {
                Log::error('Failed to process a single pending employee request.', [
                    'employee_request_id' => $employeeRequest->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
        }
    }

    /**
     * Fetches employees from eHealth based on whether the party is already synced.
     * It also updates the local party with the eHealth UUID if it's missing.
     *
     * @throws ConnectionException
     */
    private function fetchEmployeesFromApi(EHealthUserLogin $event, Party $userParty): array
    {
        if ($userParty->uuid) {
            // Standard case: User's party is synced, fetch by party UUID.
            $apiFilters = [
                'legal_entity_id' => $event->legalEntity->uuid,
                'status' => Status::APPROVED->value,
                'party_id' => $userParty->uuid
            ];
            $response = EHealth::employee()->getMany($apiFilters);
        } else {
            // New user case: Party has no UUID yet, fetch by Tax ID.
            $response = EHealth::employee()->getMany(['tax_id' => $userParty->tax_id]);
            $employees = $response->peek(); // Peek at data without consuming it.

            // If we found data, update our local party with the official UUID.
            if (!empty($employees)) {
                $ehealthPartyData = $employees[0]['party'] ?? null;
                if (isset($ehealthPartyData['id'])) {
                    $userParty->update(['uuid' => $ehealthPartyData['id']]);
                    Log::info('Successfully updated party UUID from E-Health.', ['party_id' => $userParty->id]);
                }
            }
        }

        return $response->validate();
    }

    /**
     * Finds a matching employee in the API response and processes the local request.
     *
     * @throws Throwable
     */
    private function processPendingRequest(EmployeeRequest $employeeRequest, Collection $apiEmployeesCollection, User $user): void
    {
        $matchIndex = $apiEmployeesCollection->search(function ($apiEmployee) use ($employeeRequest) {
            return $apiEmployee['position'] === $employeeRequest->position
                && $apiEmployee['employee_type'] === $employeeRequest->employee_type;
        });

        if ($matchIndex === false) {
            return;
        }

        $approvedData = $apiEmployeesCollection->pull($matchIndex);

        // Prepare data structures for database operations.
        $employeeData = $this->prepareEmployeeData($approvedData, $employeeRequest, $user);
        $partyData = $this->preparePartyData($approvedData['party'] ?? []);
        $doctorData = $this->prepareDoctorData($approvedData);

        DB::transaction(function () use ($employeeData, $partyData, $doctorData, $employeeRequest) {
            $employeeModel = Employee::updateOrCreate(
                ['uuid' => $employeeData['uuid']],
                $employeeData
            );

            Repository::employee()->updateDetails(
                $employeeModel,
                $partyData,
                $approvedData['party']['documents'] ?? [],
                $approvedData['party']['phones'] ?? [],
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
     * Prepares the data array for the Employee model.
     */
    private function prepareEmployeeData(array $approvedData, EmployeeRequest $employeeRequest, \App\Models\User $user): array
    {
        $employeeApiKeys = ['id', 'status', 'position', 'employee_type', 'start_date', 'end_date', 'is_active'];
        $employeeData = array_intersect_key($approvedData, array_flip($employeeApiKeys));

        $employeeData['uuid'] = $employeeData['id'];
        unset($employeeData['id']);

        return array_merge($employeeData, [
            'legal_entity_uuid' => $approvedData['legal_entity']['id'] ?? null,
            'legal_entity_id' => $employeeRequest->legal_entity_id,
            'party_id' => $employeeRequest->party_id,
            'user_id' => $user->id,
            'division_id' => $employeeRequest->division_id,
        ]);
    }

    /**
     * Prepares the data array for the Party model.
     */
    private function preparePartyData(array $apiPartyData): array
    {
        $partyApiKeys = ['id', 'first_name', 'last_name', 'second_name', 'birth_date', 'gender', 'no_tax_id', 'tax_id', 'email'];
        $partyData = array_intersect_key($apiPartyData, array_flip($partyApiKeys));

        if (isset($partyData['id'])) {
            $partyData['uuid'] = $partyData['id'];
            unset($partyData['id']);
        }

        return $partyData;
    }

    /**
     * Extracts doctor-specific data based on employee type.
     */
    private function prepareDoctorData(array $approvedData): array
    {
        $doctorDataKey = strtolower($approvedData['employee_type'] ?? '');
        return $approvedData[$doctorDataKey] ?? [];
    }

    /**
     * Assigns the appropriate role to the user if they don't already have it.
     */
    private function assignRoleToUser(Employee $employeeModel): void
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
    private function finalizeEmployeeRequest(EmployeeRequest $employeeRequest, Employee $employeeModel): void
    {
        $employeeRequest->status = Status::APPROVED->value;
        $employeeRequest->applied_at = now();
        $employeeRequest->employee()->associate($employeeModel);
        $employeeRequest->save();

        if ($employeeRequest->revision()->exists()) {
            $employeeRequest->revision->update(['status' => RevisionStatus::APPLIED->value]);
        }
    }
}
