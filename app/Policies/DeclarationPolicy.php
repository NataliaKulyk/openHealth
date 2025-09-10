<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Declaration;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeclarationPolicy
{
    /**
     * Determine whether the user can view any declaration.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('declaration:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view declaration.
     */
    public function view(User $user, Declaration $declaration): Response
    {
        if ($user->cannot('declaration:read')) {
            return Response::denyWithStatus(404);
        }

        return $user->employees()->whereKey($declaration->employee_id)->exists()
            ? Response::allow()
            : Response::denyWithStatus(404);
    }
}
