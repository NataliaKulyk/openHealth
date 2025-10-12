<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Repositories\Repository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmployeeCreate
{
    /**
     * @throws Throwable
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;

        // Guard clause: Ensure the user has the necessary scope from eHealth.
        // This prevents a guaranteed '403 Forbidden' error from the API call
        // if a user with a limited role (e.g., "Assistant") logs in.
        // if (!$user->can('employee:read')) {
            // return;
        // }

        $employeeRequests = EmployeeRequest::with('revision')
            ->where('status', RequestStatus::SIGNED)
            ->where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($employeeRequests->isEmpty()) {
            return;
        }

        $taxId = $employeeRequests->first()->revision->data['party']['tax_id'];
        $employees = EHealth::employee()->getMany(
            [
                'legal_entity_id' => $event->legalEntity->uuid,
                'tax_id' => $taxId,
                'status' => 'APPROVED',
            ]
        )->validate();

        if (empty($employees)) {
            return;
        }

        $existingUuids = $user->employees()->pluck('uuid')->all();
        $employees = array_filter($employees, fn (array $employee) => !in_array($employee['uuid'], $existingUuids));

        if (empty($employees)) {
            return;
        }

        $newRoles = [];

        DB::transaction(function () use ($user, $employees, $employeeRequests, $event, &$newRoles) {
            foreach ($employees as $eHealthEmployee) {
                $employeeRequest = $this->findMatchingLocalRequest($employeeRequests, $eHealthEmployee);

                if (!$employeeRequest) {
                    continue;
                }

                $dataFromRevision = EHealth::employeeRequest()->mapCreate($employeeRequest->revision->data);
                $dataFromEHealth = Arr::only($eHealthEmployee, ['uuid', 'position', 'employee_type', 'start_date', 'end_date']);

                $newEmployee = Employee::updateOrCreate(
                    ['uuid' => $dataFromEHealth['uuid']],
                    array_merge($dataFromRevision['employee'], $dataFromEHealth, [
                        'legal_entity_id' => $event->legalEntity->id,
                        'legal_entity_uuid' => $event->legalEntity->uuid,
                        'user_id' => $user->id
                    ])
                );

                $newEmployee = Repository::employee()->updateDetails(
                    $newEmployee,
                    array_merge($dataFromRevision['party'], $eHealthEmployee['party'], ['user_id' => $user->id]),
                    $dataFromRevision['documents'],
                    $dataFromRevision['phones'],
                    $dataFromRevision['educations'] ?? null,
                    $dataFromRevision['specialities'] ?? null,
                    $dataFromRevision['qualifications'] ?? null,
                    $dataFromRevision['scienceDegree'] ?? null
                );

                $employeeRequest->update(
                    [
                        'employee_id' => $newEmployee->id,
                        'status' => RequestStatus::APPROVED,
                        'applied_at' => now(),
                        'user_id' => $user->id,
                        'party_id' => $newEmployee->partyId,
                    ]
                );
                $employeeRequest->revision->update(['status' => RevisionStatus::APPLIED]);

                if (!$user->hasRole($newEmployee->employeeType)) {
                    $newRoles[] = $newEmployee->employeeType;
                }
            }
        });

        if (!empty($newRoles)) {
            setPermissionsTeamId($event->legalEntity->id);
            $user->unsetRelation('roles')->unsetRelation('permissions');
            $user->assignRole($newRoles);
        }
    }

    /**
     * This matching logic is fragile as it relies on text fields.
     * A more robust solution would be to use a unique token exchanged during the signing process.
     * This implementation is kept for now but should be considered for a future upgrade.
     */
    private function findMatchingLocalRequest(Collection $employeeRequests, array $employee): ?EmployeeRequest
    {
        return $employeeRequests->where('position', $employee['position'])
            ->where('employee_type', $employee['employee_type'])
            ->first(function (EmployeeRequest $employeeRequest) use ($employee) {
                $party = $employeeRequest->revision->data['party'];
                $namesMatch = $party['first_name'] === $employee['party']['first_name']
                    && $party['last_name'] === $employee['party']['last_name']
                    && $party['second_name'] === $employee['party']['second_name'];

                $eHealthDateString = $employee['start_date'] ?? null;

                if (is_null($eHealthDateString) || is_null($employeeRequest->start_date)) {
                    return false;
                }

                $datesMatch = Carbon::parse($employeeRequest->start_date)
                    ->isSameDay(Carbon::parse($eHealthDateString));

                return $namesMatch && $datesMatch;
            });
    }
}
