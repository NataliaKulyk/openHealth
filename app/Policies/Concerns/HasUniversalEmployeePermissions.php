<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait HasUniversalEmployeePermissions
{
    /**
     * Get the name of the currently active authentication guard.
     */
    private function getCurrentGuard(): string
    {
        foreach (array_keys(config('auth.guards')) as $guard) {
            if (auth($guard)->check()) {
                return $guard;
            }
        }
        return config('auth.defaults.guard');
    }

    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Grant all abilities to a Super Admin on the 'web' guard
        if ($this->getCurrentGuard() === 'web' && $user->hasRole('Super Admin')) {
            return true;
        }
        return null;
    }

    /**
     * Universal permission checker with guard and legal entity context.
     */
    private function checkPermission(User $user, string $permission): bool
    {
        $guard = $this->getCurrentGuard();

        // Scoped permission query that considers legal_entity_id
        $legalEntity = legalEntity();
        if (!$legalEntity) {
            return false;
        }

        return $user->roles()
            ->wherePivot('legal_entity_id', $legalEntity->id)
            ->whereHas('permissions', function ($query) use ($permission, $guard) {
                $query->where('name', $permission)->where('guard_name', 'like', $guard);
            })
            ->exists();
    }
}
