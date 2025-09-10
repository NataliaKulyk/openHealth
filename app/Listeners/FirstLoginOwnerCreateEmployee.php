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

class FirstLoginOwnerCreateEmployee
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(EHealthUserLogin $event): void
    {
        if (!$event->isFirstLogin || !$event->user->hasRole('OWNER')) {
            return;
        }

        try {
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
            ['id' => $uuid, 'position' => $position, 'party' => ['id' => $partyUuid]] = data_get($ehealthResponse->getData(), '0');

            $employeeData = compact('uuid', 'position', 'partyUuid');

            if (count(array_filter($employeeData)) !== count($employeeData)) {
                return;
            }

            DB::transaction(function () use ($event, $userParty, $employeeData, $pendingRequests) {
                $userParty->update([
                    'uuid' => $employeeData['partyUuid'],
                    'user_id' => $event->user->id
                ]);

                // Create the Employee record for the OWNER
                $employee = Employee::create([
                    'uuid' => $employeeData['uuid'],
                    'legal_entity_uuid' => $event->legalEntity->uuid,
                    'legal_entity_id' => $event->legalEntity->id,
                    'position' => $employeeData['position'],
                    'start_date' => $pendingRequests->start_date,
                    'employee_type' => 'OWNER',
                    'status' => Status::APPROVED->value,
                    'user_id' => $event->user->id,
                    'party_id' => $userParty->id,
                ]);

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
        } catch (Throwable $err) {
            Log::error('Failed to process OWNER\'s employee creating on login.', [
                'user_id' => $event->user->id,
                'error_message' => $err->getMessage(),
                'trace' => $err->getTraceAsString(),
            ]);

            throw $err;
        }
    }
}
