<?php

declare(strict_types=1);

namespace App\Listeners\Ehealth;

use App\Classes\eHealth\EHealth;
use App\Events\EHealthUserLogin;

class EmployeeNewCreate
{
    /**
     * Handle the event.
     */
public function handle(EHealthUserLogin $event): void
{
// The party should already exist due to the employee creation process
    if (!$event->user->party->exists()) return;

    $party = $event->user->party;

    // If EHealth UUID is already set, the user already synchronized with EHealth
    if (isset($party->uuid)) return;

    // If email isn't set, the user wasn't created through this MIS
    if (!isset($party->email)) return;

    // Get pending employee requests for the current user
    $pendingRequests = $event->user->employeeRequests()
        ->where('legal_entity_id', $event->legalEntity->id)
        ->whereNull('employee_id')
        ->where('party_id', $party->id);

    // Ask EHealth for employees data
    $response = EHealth::employee()->getMany([
        'legal_entity_id' => $event->legalEntity->uuid,
        'tax_id' => $party->taxId,
    ]);

        $employees = $response->validate();

        foreach ($employees as $employee) {

        }

    }
}
