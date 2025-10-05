<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Repositories\Repository;
use Illuminate\Support\Collection;

class EmployeeCreate
{
    /**
     * Is applied only for users/employees added through the MIS
     * Create a new employee and party (not exists) during first login. The data is retrieved from the revisions table
     * First performing a lookup in the employee_requests table if the user has any pending requests with a status of SIGNED.
     * If such a request exists, get associated revision from the revisions table
     * use the data from the revision to make request to the E-Health API to get the list of associated employees
     *
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;

        /**
         * Get associated email from the revision table. Comparing email during registration with email from the E-Health user details API response
         * is the only reliable way to match the user to their newly created employee record(s) in E-Health.
         */
        $employeeRequests = EmployeeRequest::with('revision')
            ->where('status', RequestStatus::SIGNED)
            ->where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        dd($employeeRequests);

        if ($employeeRequests->isEmpty()) {
            return;
        }

        $taxId = $employeeRequests->first()->revision->data['party']['tax_id'];

        $employees = EHealth::employee()->getMany([
            'legal_entity_id' => $event->legalEntity->uuid,
            'tax_id' => $taxId,
            'status' => 'APPROVED',
        ])->validate();

        if (empty($employees)) {
            return;
        }

        $existingUuids = $user->employees()->pluck('uuid')->all();

        // Filter out employees that already exist in the local database
        $employees = array_filter($employees, fn(array $employee) => !in_array($employee['uuid'], $existingUuids));

        if (empty($employees)) {
            return;
        }

        // Synchronize all new employees
        setPermissionsTeamId($event->legalEntity->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');
        foreach ($employees as $employee) {

            // Find correspondent employee request
            $employeeRequest = $this->findEmployeeRequest($employeeRequests, $employee);

            /**
             * Haven't found a matching request, skip this employee
             * TODO We might try to create an employee with partial data from E-Health
             */
            if (!$employeeRequest) {
                continue;
            }

            $dataLocal = EHealth::employeeRequest()->mapCreate($employeeRequest->revision->data);
            $employeeEhealth = Arr::only($employee, ['uuid', 'position', 'employee_type', 'start_date', 'end_date']);
            $newEmployee = new Employee(array_merge($dataLocal['employee'], $employeeEhealth));
            $newEmployee->save();

            $newEmployee = Repository::employee()->updateDetails(
                $newEmployee,
                array_merge($dataLocal['party'], $employee['party'], ['user_id' => $user->id]),
                $dataLocal['documents'],
                $dataLocal['phones'],
                $dataLocal['educations'] ?? null,
                $dataLocal['specialities'] ?? null,
                $dataLocal['qualifications'] ?? null,
                $dataLocal['scienceDegree'] ?? null
            );

            $employeeRequest->save(['employee_id' => $newEmployee->id, 'status' => RequestStatus::APPROVED, 'applied_at' => now(), 'user_id' => $user->id]);

            if (!$user->hasRole($newEmployee->employeeType)) {
                $user->assignRole($newEmployee->employeeType);
            }
        }
    }

    protected function findEmployeeRequest(Collection $employeeRequests, array $employee): ?EmployeeRequest
    {
        return $employeeRequests->where('position', $employee['position'])
            ->where('employee_type', $employee['employee_type'])
            ->first(function (EmployeeRequest $employeeRequest) use ($employee) {
                $party = $employeeRequest->revision->data['party'];
                return $party['first_name'] == $employee['party']['first_name']
                    && $party['last_name'] == $employee['party']['last_name']
                    && $party['second_name'] == $employee['party']['second_name'];
            });
    }
}
