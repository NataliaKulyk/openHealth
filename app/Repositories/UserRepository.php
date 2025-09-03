<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Division;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository
{
    /**
     * @param $party
     * @param $role
     * @return User|null
     */
    public function createIfNotExist($party, $role): User|null
    {
        if (empty($party['email'])) {
            return null;
        }

        // Create User if not exists
        $user = User::firstOrCreate(
            [
                'email' => $party['email']
            ],
            [
                'password' => Hash::make(Str::random(8))
            ]
        );

        // Set Role     //TODO: need more examine is this need for all user creation cases...
        auth()->shouldUse('web');

        $user->assignRole($role);

        auth()->shouldUse('ehealth');
        $user->assignRole($role);

        $user->save();

        return $user;
    }

    /**
     * Get list of users that:
     * 1) have permission 'division:write'
     * 2) have an employee in the updated division
     * 3) and this employee belongs to the same legal_entity
     *
     * @param  Division  $division
     * @return Collection
     */
    public function getDivisionEditorsByLegalEntity(Division $division): Collection
    {
        return User::permission('division:write')
            ->whereHas('employees', static function (Builder $query) use ($division) {
                $query->where('division_id', $division->id)
                    ->where('legal_entity_id', legalEntity()->id);
            })
            ->get();
    }

    /**
     * Get a collection of users who have the "OWNER" role and are linked as employees to the current legal entity.
     *
     * @return Collection
     */
    public function getLegalEntityOwners(): Collection
    {
        return User::role('OWNER')
            ->whereHas('employees', static function (Builder $query) {
                $query->where('legal_entity_id', legalEntity()->id);
            })
            ->get();
    }
}
