<?php

namespace App\Policies;

use App\Models\Employee\EmployeeRequest;
use App\Models\User;

class EmployeeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee_request:read', 'ehealth');
    }

    public function view(User $user, EmployeeRequest $employeeRequest): bool
    {
        return $user->hasPermissionTo('employee_request:read', 'ehealth');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('employee_request:write', 'ehealth');
    }

    public function update(User $user, EmployeeRequest $employeeRequest): bool
    {
        if (!is_null($employeeRequest->uuid)) return false;
        return $user->hasPermissionTo('employee_request:write', 'ehealth');
    }

    public function delete(User $user, EmployeeRequest $employeeRequest): bool
    {
        if (!is_null($employeeRequest->uuid)) return false;
        return $user->hasPermissionTo('employee_request:write', 'ehealth');
    }
}
