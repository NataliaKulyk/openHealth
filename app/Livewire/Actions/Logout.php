<?php

namespace App\Livewire\Actions;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Classes\eHealth\Request as eHealthRequest;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(bool $redirect = true)
    {
        if (auth('ehealth')->check()
            && (session()->has(config('ehealth.api.auth_ehealth'))
            || session()->has(config('ehealth.api.oauth.bearer_token')))
        ) {
            new eHealthRequest('POST', config('ehealth.api.oauth.logout'), [])->sendRequest();
        }

        $sessionId = request()->session()->getId();

        if (config('session.driver') === 'database') {
            Session::getHandler()->destroy($sessionId);
        }

        Auth::logout();

        session()->invalidate();
        session()->regenerateToken();

        return $redirect ? redirect('/login') : true;
    }
}
