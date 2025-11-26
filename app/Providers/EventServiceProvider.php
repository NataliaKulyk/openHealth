<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\EHealthUserLogin;
use App\Events\EhealthUserVerified;
use App\Events\LegalEntityCreate;
use App\Listeners\eHealth\EmployeeCreate;
use App\Listeners\eHealth\EmployeeRequestActualize;
use App\Listeners\eHealth\EmployeeUpdate;
use App\Listeners\FirstLoginOwnerSynchronization;
use App\Listeners\OnRegularLoginSyncronization;
use App\Listeners\PartyVerificationSyncStatusOnLogin;
use App\Listeners\SendUserCredentialsListener;
use App\Listeners\SyncUserRolesAfterVerification;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\LogLockout;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Lockout::class => [
            LogLockout::class
        ],

        EHealthUserLogin::class => [
            // 1. GLOBAL SYNC (Queued)
            // We start the heavy synchronization of the first entry of the overer.
            // The class implements ShouldQueue, so it's just sending the task to Redis/DB.
            FirstLoginOwnerSynchronization::class,

            // 2. ERROR RECOVERY (Queued)
            // If this is not the first entrance, we check if the previous batches have "fallen off".
            // Also, asynchronous.
            OnRegularLoginSyncronization::class,

            // --- After sending tasks to the queue, we perform synchronous actions ---

            // 3. LOCAL CREATION (Sync)
            // Creating an Employee and linking a Party based on signed requests (Creation only).
            EmployeeCreate::class,

            // 4. PERSONAL UPDATE (Sync)
            // Updating the data of the current user based on signed requests (Updates only).
            EmployeeUpdate::class,

            // 5. GLOBAL SYNC (Async Job Trigger)
            // Updating all requests for the legal entity in background.
            EmployeeRequestActualize::class,

            // 6. VERIFICATION STATUS
            PartyVerificationSyncStatusOnLogin::class,
        ],

        EhealthUserVerified::class => [
            SyncUserRolesAfterVerification::class,
        ],

        LegalEntityCreate::class => [
            SendUserCredentialsListener::class,
        ],
    ];

    /**
     * Turn off auto discover so that Laravel clearly follows the order in the $listen array.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
