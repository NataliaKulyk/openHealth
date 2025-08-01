<?php

namespace App\Listeners;

use Log;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;

class EmailVerification extends SendEmailVerificationNotification
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
    public function handle(object $event): void
    {
        try {
            parent::handle($event);
        } catch (Exception $err) {
            Log::error('EmailVerification Listener:', ['error' => $err->getMessage()]);

            throw new Exception(__("EmailVerification Listener: Cannot send verification email to the {$event->user->email}"));
        }
    }
}
