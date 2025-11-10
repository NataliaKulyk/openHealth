<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Equipment\Status;
use App\Models\Equipment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EquipmentPolicy
{
    /**
     * User allowed to view the list of equipment
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('equipment:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User allowed to synchronize equipments.
     */
    public function sync(User $user): Response
    {
        if ($user->cannot('equipment:write') && $user->cannot('equipment:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User allow to create equipment
     */
    public function create(User $user): Response
    {
        if ($user->cannot('equipment:write')) {
            return Response::denyWithStatus(404);
        }

        // Check legal entity validity.
        if (legalEntity()->isActive && !in_array(legalEntity()->status, ['ACTIVE', 'SUSPENDED'], true)) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can edit the model.
     */
    public function edit(User $user, Equipment $equipment): Response
    {
        // Should belong to the same legal entity
        if ($equipment->legalEntityId !== legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        if ($user->cannot('equipment:write')) {
            return Response::denyWithStatus(404);
        }

        // Only draft can be edited
        if ($equipment->status !== Status::DRAFT) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
