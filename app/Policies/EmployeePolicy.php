<?php

namespace App\Policies;

use App\Models\Employee\Employee;
use App\Models\User;
use App\Policies\Concerns\HasUniversalEmployeePermissions;

class EmployeePolicy
{
    use HasUniversalEmployeePermissions;

    private function belongsToEntity(Employee $employee): bool
    {
        return (int)$employee->legal_entity_id === (int)legalEntity()->id;
    }

    public function view(User $user, Employee $employee): bool
    {
        return $this->belongsToEntity($employee)
            && $this->checkPermission($user, 'employee:details');
    }

    public function update(User $user, Employee $employee): bool
    {
        return $this->belongsToEntity($employee)
            && $this->checkPermission($user, 'employee:write');
    }

    public function deactivate(User $user, Employee $employee): bool
    {
        return $this->belongsToEntity($employee)
            && $this->checkPermission($user, 'employee:deactivate');
    }
}
