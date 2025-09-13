<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class PersonPolicy
{
    /**
     * Determine whether the user can view the person request.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('person:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
