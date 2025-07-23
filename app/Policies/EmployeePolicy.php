<?php

namespace App\Policies;

use App\Models\Employee\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Check if the employee belongs to the current legal entity.
     * @param Employee $employee
     * @return bool
     */
    private function belongsToEntity(Employee $employee): bool
    {
        return (int)$employee->legal_entity_id === (int)legalEntity()->id;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee:read', 'ehealth');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        return $this->belongsToEntity($employee)
            && $user->hasPermissionTo('employee:details', 'ehealth');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Employee $employee): bool
    {
        return $this->belongsToEntity($employee)
            && $user->hasPermissionTo('employee:write', 'ehealth');
    }

    /**
     * Determine whether the user can deactivate the model.
     */
    public function deactivate(User $user, Employee $employee): bool
    {
        return $this->belongsToEntity($employee)
            && $user->hasPermissionTo('employee:deactivate', 'ehealth');
    }
}
