<?php

namespace App\Policies;

use App\Models\Employee\EmployeeRequest;
use App\Models\User;

class EmployeeRequestPolicy
{
    /**
     * Check if the user has the read permission.
     */
    private function canRead(User $user): bool
    {
        return $user->hasPermissionTo('employee_request:read', 'ehealth');
    }

    /**
     * Check if the user has the write permission.
     */
    private function canWrite(User $user): bool
    {
        return $user->hasPermissionTo('employee_request:write', 'ehealth');
    }

    /**
     * Check if the request belongs to the current legal entity.
     */
    private function belongsToEntity(EmployeeRequest $employeeRequest): bool
    {
        return (int)$employeeRequest->legal_entity_id === (int)legalEntity()->id;
    }

    /**
     * Check if the request is editable (i.e., not yet sent to e-Health).
     */
    private function isEditable(EmployeeRequest $employeeRequest): bool
    {
        return $this->belongsToEntity($employeeRequest) && is_null($employeeRequest->uuid);
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canRead($user);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmployeeRequest $employeeRequest): bool
    {
        return $this->belongsToEntity($employeeRequest) && $this->canRead($user);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canWrite($user);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmployeeRequest $employeeRequest): bool
    {
        return $this->isEditable($employeeRequest) && $this->canWrite($user);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmployeeRequest $employeeRequest): bool
    {
        return $this->isEditable($employeeRequest) && $this->canWrite($user);
    }
}
