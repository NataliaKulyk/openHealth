<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    /**
     * Determine whether the user can view any contract.
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('contract_request:read') && $user->cannot('contract:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User allow to create contract.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('contract_request:create')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
