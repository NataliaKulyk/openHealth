<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LegalEntity;
use App\Models\License;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LicensePolicy
{
    /**
     * User can read the license
     */
    public function access(User $user, License $currentLicense, ?LegalEntity $currentLegalEntity = null): Response
    {
        if (is_null($currentLegalEntity)) {
            $currentLegalEntity = legalEntity();
        }

        // Should belong to the same legal entity
        if ($currentLicense->legalEntity->id !== $currentLegalEntity->id) {
            return Response::denyWithStatus(404);
        }

        if ($user->cannot('license:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User can edit the license
     */
    public function write(User $user, License $currentLicense, ?LegalEntity $currentLegalEntity = null): Response
    {
        if (is_null($currentLegalEntity)) {
            $currentLegalEntity = legalEntity();
        }

        // Should belong to the same legal entity
        if ($currentLicense->legalEntity->id !== $currentLegalEntity->id) {
            return Response::denyWithStatus(404);
        }

        if ($user->cannot('license:write')) {
            return Response::denyWithStatus(404);
        }

        // Can't write to the main license
        if ($currentLicense->isPrimary) {
            return Response::denyWithStatus(403, __('errors.policy.licence.primary_not_editable'));
        }

        return Response::allow();
    }
}
