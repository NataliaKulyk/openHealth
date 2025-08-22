<?php

namespace App\Policies;

use App\Models\Employee\EmployeeRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeeRequestPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('employee_request:read')
            ? Response::allow()
            : Response::deny(__('employees.policy.req.view_any_denied'));
    }

    public function view(User $user, EmployeeRequest $employeeRequest): Response
    {
        if ((int)$employeeRequest->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee_request:details')
            ? Response::allow()
            : Response::deny(__('employees.policy.req.view_denied'));
    }

    public function create(User $user): Response
    {
        return $user->can('employee_request:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.req.create_denied'));
    }

    public function update(User $user, EmployeeRequest $employeeRequest): Response
    {
        if ((int)$employeeRequest->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        if (!is_null($employeeRequest->uuid)) {
            return Response::deny(__('employees.policy.req.processed_no_edit'));
        }

        return $user->can('employee_request:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.req.update_denied'));
    }

    public function delete(User $user, EmployeeRequest $employeeRequest): Response
    {
        if ((int)$employeeRequest->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        if (!is_null($employeeRequest->uuid)) {
            return Response::deny(__('employees.policy.req.processed_no_delete'));
        }

        return $user->can('employee_request:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.req.delete_denied'));
    }
}
