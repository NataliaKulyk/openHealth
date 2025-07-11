<?php

namespace App\Repositories;

use App\Events\ApplyUserTeamId;
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

        // Set Role     //TODO: need more examine is this need for all user creation cases...
        auth()->shouldUse('web');

        $user->assignRole($role);

        auth()->shouldUse('ehealth');
        $user->assignRole($role);

        $user->save();

        return $user;
    }
}
