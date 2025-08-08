<?php

namespace App\Policies;

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
        if ((int)$employee->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee:details')
            ? Response::allow()
            : Response::deny(__('employees.policy.view_denied'));
    }

    public function update(User $user, Employee $employee): Response
    {
        if ((int)$employee->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.update_denied'));
    }

    public function deactivate(User $user, Employee $employee): Response
    {
        if ((int)$employee->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee:deactivate')
            ? Response::allow()
            : Response::deny(__('employees.policy.deactivate_denied'));
    }
}
