<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\Auth;

class LegalEntityPolicy
{
    /**
     * Determine if the user has access to the legal entity
     */
    public function access(User $user, LegalEntity $currentEntity): Response
    {
        $legalEntitiesIds = $user->employees->pluck('legal_entity_id')->toArray();

        $shouldAllow = in_array($currentEntity->id, $legalEntitiesIds);

        if (!$shouldAllow) {
            return Response::denyWithStatus(404);
        }

        app()->bind(LegalEntity::class, fn () => $currentEntity);
        app()->alias(LegalEntity::class, 'legalEntity');

        setPermissionsTeamId($currentEntity->id);

        return Response::allow();
    }

    /**
     * Determine if the user can create a legal entities
     *
     * @param  User  $user
     *
     * @return true|Response
     */
    public function create(User $user): true|Response
    {
        // Temporarily. Available for all unconnected users (to the LegalEntity). Until change for real logic
        if ($user->roles->isEmpty()) {
            return true;
        }

        if ($user->hasAnyRole(['OWNER', 'ADMIN', 'HR'])) {
            return Response::allow();
        }

        return Response::deny('This action is unauthorized.');
    }

    public function edit(User $user): Response
    {
        if ($user->hasRole(['OWNER']) && Auth::guard('ehealth')->check()) {
            return Response::allow();
        }

        return Response::deny('This action is unauthorized.');
    }
}
