<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ContractPolicy
{
    /**
     * Determine whether the user can view any contracts (list).
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('contract_request:read') && $user->cannot('contract:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can view a specific contract.
     * Fixes 403 Forbidden error on "Show" page.
     */
    public function view(User $user, Contract $contract): Response
    {
        // 1. Strict check: Contract must belong to the current Legal Entity
        if ((int)$contract->legal_entity_id !== (int)legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        // 2. Permission check
        if ($user->cannot('contract_request:read') && $user->cannot('contract:read')) {
            return Response::deny(__('contracts.policy.view_denied'));
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can create contracts.
     */
    public function create(User $user): Response
    {
        if ($user->cannot('contract_request:create')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can synchronize contracts with eHealth.
     */
    public function sync(User $user): Response
    {
        if ($user->cannot('contract_request:read')) {
            return Response::deny(__('contracts.policy.sync_denied'));
        }

        return Response::allow();
    }

}
