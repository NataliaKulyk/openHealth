<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractRequestPolicy
{
    /**
     * User allow to create contract.
     */
    public function initialize(User $user): Response
    {
        if ($user->cannot('contract_request:create')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
