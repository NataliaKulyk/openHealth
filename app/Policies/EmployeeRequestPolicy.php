<?php

namespace App\Policies;

use App\Models\Employee\EmployeeRequest;
use App\Models\User;
use App\Policies\Concerns\HasUniversalEmployeePermissions;

class EmployeeRequestPolicy
{
    use HasUniversalEmployeePermissions;

    private function belongsToEntity(EmployeeRequest $employeeRequest): bool
    {
        return (int)$employeeRequest->legal_entity_id === (int)legalEntity()->id;
    }

    private function isEditable(EmployeeRequest $employeeRequest): bool
    {
        return $this->belongsToEntity($employeeRequest) && is_null($employeeRequest->uuid);
    }

    public function view(User $user, EmployeeRequest $employeeRequest): bool
    {
        return $this->belongsToEntity($employeeRequest)
            && $this->checkPermission($user, 'employee_request:read');
    }

    public function create(User $user): bool
    {
        return $this->checkPermission($user, 'employee_request:write');
    }

    public function update(User $user, EmployeeRequest $employeeRequest): bool
    {
        return $this->isEditable($employeeRequest)
            && $this->checkPermission($user, 'employee_request:write');
    }

    public function delete(User $user, EmployeeRequest $employeeRequest): bool
    {
        return $this->isEditable($employeeRequest)
            && $this->checkPermission($user, 'employee_request:write');
    }
}
