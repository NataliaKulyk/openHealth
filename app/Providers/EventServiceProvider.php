<?php

namespace App\Providers;

use App\Events\ApplyUserTeamId;
use App\Listeners\ApplyUserTeamIdListener;
use App\Listeners\EmailVerification;
use App\Listeners\LogLockout;
use App\Events\LegalEntityCreate;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Registered;
use App\Listeners\SendUserCredentialsListener;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            EmailVerification::class,
        ],
        Lockout::class => [
            LogLockout::class
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return true;
    }
}
