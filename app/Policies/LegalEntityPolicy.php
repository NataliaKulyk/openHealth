<?php

namespace App\Policies;

use App\Models\User;
use App\Models\LegalEntity;
use Illuminate\Auth\Access\Response;

class LegalEntityPolicy
{
   /**
    * Determine if the user has access to the legal entity
    */
   public function access(User $user, LegalEntity $currentEntity): Response
   {
       $legalEntitiyIds = $user->employees->pluck('legal_entity_id')->toArray();

       $shouldAllow = in_array($currentEntity->id, $legalEntitiyIds);

       if (!$shouldAllow) {
           return Response::denyWithStatus(404);
       }

       app()->bind(LegalEntity::class, fn () => $currentEntity);
       app()->alias(LegalEntity::class, 'legalEntity');

       setPermissionsTeamId($currentEntity->id);

       return Response::allow();
   }

   /**
    * Determine if the user can create an legal entities
    *
    * @param \App\Models\User $user
    *
    * @return bool|Response
    */
   public function create(User $user)
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

   public function edit(User $user)
   {
        if ($user->hasRole(['OWNER'])) {
            return Response::allow();
        }

        return Response::deny('This action is unauthorized.');
   }
}
