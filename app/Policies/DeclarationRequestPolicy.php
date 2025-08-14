<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class DeclarationRequestPolicy
{
    /**
     * Determine whether the user can create declaration request.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('declaration_request:write')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create declaration request.
     */
    public function approve(User $user): Response
    {
        if ($user->cannot('declaration_request:approve')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can reject declaration request.
     */
    public function reject(User $user): Response
    {
        if ($user->cannot('declaration_request:reject')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can sign declaration request.
     */
    public function sign(User $user): Response
    {
        if ($user->cannot('declaration_request:sign')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
