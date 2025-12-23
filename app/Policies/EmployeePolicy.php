<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Status;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('employee:read')
            ? Response::allow()
            : Response::deny(__('employees.policy.view_any_denied'));
    }

    public function view(User $user, Employee $employee): Response
    {
        if ((int) $employee->legalEntityId !== (int) legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee:details')
            ? Response::allow()
            : Response::deny(__('employees.policy.view_denied'));
    }

    public function update(User $user, Employee $employee): Response
    {
        // 1. Prohibition of owner editing
        if ($employee->employeeType === 'OWNER') {
            return Response::deny(__('employees.policy.owner_no_edit'));
        }

        // 2. Checking for communication with the user (what you did before)
        if (is_null($employee->userId)) {
            return Response::deny(__('employees.policy.no_user_linked'));
        }

        // 3. Status check
        if ($employee->status === Status::DISMISSED) {
            return Response::deny(__('employees.policy.emp.dismissed_no_edit'));
        }

        // 4. Verification of rights
        return $user->can('employee:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.emp.update_denied'));
    }

    public function deactivate(User $user, Employee $employee): Response
    {
        if ((int) $employee->legalEntityId !== (int) legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee:deactivate')
            ? Response::allow()
            : Response::deny(__('employees.policy.deactivate_denied'));
    }

    /**
     * Determine whether the user can sync the employee with eHealth.
     */
    public function sync(User $user, Employee $employee): Response
    {
        // 1. Verification of affiliation with the current institution
        if ((int) $employee->legalEntityId !== (int) legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        // 2. State Check
        if (!$employee->userId || !$employee->partyId || !$employee->uuid) {
            return Response::deny(__('employees.policy.sync_missing_data'));
        }

        // 3. PERMISSIONS
        return $user->can('employee:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.emp.update_denied'));
    }
}
