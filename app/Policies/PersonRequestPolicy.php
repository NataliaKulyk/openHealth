<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class PersonRequestPolicy
{
    /**
     * User allowed to view the list of person requests
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('person_request:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view the person request.
     */
    public function view(User $user): Response
    {
        if ($user->cannot('person_request:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create person request.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('person_request:write')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
