<?php

namespace App\Repositories;

use App\Models\LegalEntity;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository
{
    /**
     * @param $email
     * @param $role
     * @return User|null
     */

    public function createIfNotExist($party, $role, LegalEntity $legalEntity): User|null
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
                'password' => Hash::make(\Illuminate\Support\Str::random(8))
            ]
        );

        // Set Role
        auth()->shouldUse('ehealth'); // TODO: examine is this suitable for all user creation cases...

        setPermissionsTeamId($legalEntity->id); // TODO: this need to additional checking
        $user->unsetRelation('roles');

        $user->assignRole($role);
        $user->save();

        return $user;
    }
}
