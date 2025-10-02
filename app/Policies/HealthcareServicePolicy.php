<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Enums\Status;
use App\Models\LegalEntity;
use App\Models\HealthcareService;
use Illuminate\Auth\Access\Response;

class HealthcareServicePolicy
{
    /**
     * User allowed to view the list of healthcare services
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('healthcare_service:read')) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User allow to create the healthcare services
     */
    public function create(User $user): Response
    {
        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(404);
        }

        // Check that legal entity type exists in HEALTHCARE_SERVICE_LEGAL_ENTITIES_ALLOWED_TYPES chart parameter.
        $types = dictionary()->getDictionary('LEGAL_ENTITY_TYPE_V2', false)->getKeys();
        if (!in_array(legalEntity()->type, $types, true)) {
            return Response::denyWithStatus(404);
        }

        // The healthcare service can be created for legal entities with the following statuses.
        if (!in_array(legalEntity()->status, ['ACTIVE', 'SUSPENDED'], true)) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }

    /**
     * User allow to delete the healthcare services draft record fromm the DB
     */
    public function delete(User $user, HealthcareService $healthcareService): Response
    {
        // Only HealthcareServices with DRAFT status can be deleted
        if ($healthcareService->status !== Status::DRAFT) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, HealthcareService $healthcareService, ?LegalEntity $legalEntity = null): Response
    {
        if (is_null($legalEntity)) {
            $legalEntity = legalEntity();
        }

        // If got null instead HealthcareService model
        if (empty($healthcareService)) {
            return Response::denyWithStatus(404);
        }

        // Should belong to the same legal entity
        if ($healthcareService->legal_entity_id !== (int)$legalEntity->id) {
            return Response::denyWithStatus(404);
        }

        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(404);
        }

        // Inactive healthcare services cannot be updated
        if ($healthcareService->status === Status::INACTIVE) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can activate the healthcare service.
     */
    public function activate(User $user, HealthcareService $healthcareService): Response
    {
        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(404);
        }

        // Some healthcare services cannot be activated
        if (
            $healthcareService->status === Status::ACTIVE ||
            $healthcareService->status === Status::DRAFT ||
            $healthcareService->status === Status::UNSYNCED
        ) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can deactivate the healthcare service.
     */
    public function deactivate(User $user, HealthcareService $healthcareService): Response
    {
        if ($user->cannot('healthcare_service:write')) {
            return Response::denyWithStatus(404);
        }

        // Some healthcare services cannot be deactivated
        if (
            $healthcareService->status === Status::INACTIVE ||
            $healthcareService->status === Status::DRAFT ||
            $healthcareService->status === Status::UNSYNCED
        ) {
            return Response::deny();
        }

        return Response::allow();
    }
}
