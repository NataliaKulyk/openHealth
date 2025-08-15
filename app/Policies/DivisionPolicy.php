<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\Status;
use App\Models\Division;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Builder;

class DivisionPolicy
{
    /**
     * User allowed to view the list of divisions
     */
    public function viewAny(User $user): Response
    {
        if ($user->cannot('division:read') && $user->cannot('division:details')) {
            return Response::denyWithStatus(403);
        }

        return Response::allow();
    }

    /**
     * User allow to create the Division
     */
    public function create(User $user): Response
    {
        if ($user->cannot('division:write')) {
            return Response::denyWithStatus(403);
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Division $division, ?LegalEntity $legalEntity = null): Response|bool
    {
        if (is_null($legalEntity)) {
            $legalEntity = legalEntity();
        }

        // Should belong to the same legal entity
        if ($division->legalEntity->id !== $legalEntity->id) {
            return Response::denyWithStatus(404);
        }

        if ($user->cannot('division:write')) {
            return Response::denyWithStatus(403);
        }

        if ($division->status === Status::INACTIVE) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can activate the division.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Division  $division
     *
     * @return bool
     */
    public function activate(User $user, Division $division): Response
    {
        if ($user->cannot('division:activate')) {
            return Response::denyWithStatus(403);
        }

        if ($division->status === Status::ACTIVE) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Determine whether the user can deactivate the division.
     *
     * @param  \App\Models\User  $user  The user attempting the action
     * @param  \App\Models\Division  $division  The division to be deactivated
     *
     * @return bool  True if user can deactivate the division, false otherwise
     */
    public function deactivate(User $user, Division $division): Response
    {
        if ($user->cannot('division:activate')) {
            return Response::denyWithStatus(403);
        }


        if ($this->hasAnyActiveService($division)) {
            return Response::deny();
        }

        if ($division->employees->count()) {
            return Response::deny();
        }

        if ($division->status === Status::INACTIVE) {
            return Response::deny();
        }

        return Response::allow();
    }

    /**
     * Get services associated with a division.
     *
     * @param Division $division The division to get services for
     *
     * @return Builder Query builder for division services
     */
    protected function getDivisionServices(Division $division): Builder
    {
        return HealthcareService::query()->where('division_id', $division->id);
    }

    /**
     * Check if the division has any associated service.
     *
     * @param  Division  $division  The division to check for services
     *
     * @return bool  True if the division has at least one service, false otherwise
     */
    protected function hasAnyService(Division $division): bool
    {
        return (bool)$this->getDivisionServices($division)->count();
    }

    /**
     * Checks if the division has any active service.
     *
     * @param \App\Models\Division $division The division to check
     *
     * @return bool Returns true if the division has at least one active service, false otherwise
     */
    protected function hasAnyActiveService(Division $division): bool
    {
        return (bool)$this->getDivisionServices($division)
            ->where('status', Status::ACTIVE)->count();

    }
}
