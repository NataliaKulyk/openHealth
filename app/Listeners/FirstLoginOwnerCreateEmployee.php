<?php

namespace App\Listeners;

use Throwable;
use App\Enums\Status;
use App\Models\Relations\Party;
use App\Repositories\Repository;
use App\Classes\eHealth\EHealth;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class FirstLoginOwnerCreateEmployee
{
    /**
     * Handle the event.
     */
    public function handle(EHealthUserLogin $event): void
    {
        if (!$event->isFirstLogin || !$event->user->hasRole('OWNER') || Auth::getDefaultDriver() !== 'ehealth') {
            return;
        }

        \Log::info('FirstLoginOwnerCreateEmployee listener working.', [
            'user_id' => $event->user->id,
            'legal_entity_id' => $event->legalEntity->id,
            'guard' => Auth::getDefaultDriver()
        ]);

        // Find the first pending request for the user and legal entity
        $pendingRequests = Repository::employee()->findPendingRequestsForUser($event->user, $event->legalEntity)->first();

        if (!$pendingRequests) {
            return;
        }

        // Ensure the user has an associated party
        $userParty = Party::find($pendingRequests->party_id);

        if (!$userParty) {
            return;
        }

        // Fetch the employee data from the eHealth API by owner criteria
        $filterParams = [
            'legal_entity_id' => $event->legalEntity->uuid,
            'employee_type' => 'OWNER',
            'tax_id' => $userParty->tax_id,
            'status' => Status::APPROVED->value,
        ];

        // Fetch the employee data from the eHealth API
        $ehealthResponse = EHealth::employee()->getMany($filterParams);

        ['uuid' => $uuid, 'position' => $position, 'party' => ['uuid' => $partyUuid]] = data_get($ehealthResponse->validate(), '0');

        $employeeData = compact('uuid', 'position', 'partyUuid');

        if (count(array_filter($employeeData)) !== count($employeeData)) {
            return;
        }

        $employee = new Employee();

        DB::transaction(function () use ($event, $userParty, $employeeData, $pendingRequests, $employee) {
            $userParty->update([
                'uuid' => $employeeData['partyUuid'],
                'user_id' => $event->user->id
            ]);

            $employee->fill([
                'uuid' => $employeeData['uuid'],
                'legal_entity_uuid' => $event->legalEntity->uuid,
                'legal_entity_id' => $event->legalEntity->id,
                'position' => $employeeData['position'],
                'start_date' => $pendingRequests->start_date,
                'employee_type' => 'OWNER',
                'status' => Status::APPROVED->value,
                'user_id' => $event->user->id,
                'party_id' => $userParty->id,
            ])->save();

            // Update the user's UUID to set it as the flag for completed first login
            $event->user->uuid = $event->authUserUUID;
            $event->user->save();

            // Update the pending request. Set is as approved and link to the created employee
            $pendingRequests->update([
                'employee_id' => $employee->id,
                'status' => Status::APPROVED->value,
                'applied_at' => now(),
            ]);
        });

        $userParty->refresh();
        $employee->refresh();
        $event->user->refresh();

        Auth::guard('ehealth')->login($event->user);
    }

    public function failed(EHealthUserLogin $event, Throwable $exception): void
    {
        Log::error('Listener FirstLoginOwnerCreateEmployee failed.', [
            'user_id' => $event->user->id,
            'error_message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        throw $exception;
    }
}
