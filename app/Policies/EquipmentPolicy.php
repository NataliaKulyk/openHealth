<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class EquipmentPolicy
{
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
}
