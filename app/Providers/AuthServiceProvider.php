<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Division;
use App\Policies\DivisionPolicy;
use Illuminate\Support\Facades\Auth;
use App\Auth\EHealth\Guards\EHealthGuard;
use App\Auth\EHealth\Services\TokenStorage;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Cookie\QueueingFactory;
use App\Auth\EHealth\Providers\EHealthUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Auth::extend('ehealth', static function (Application $app, string $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);
            $tokenStorage = $app->make(TokenStorage::class);

            $guard = new EHealthGuard($name, $provider, $app['session.store'], $app['request'], $tokenStorage);

            $guard->setCookieJar($app->make(QueueingFactory::class));

            return $guard;
        });

        Auth::provider('ehealth_user_provider', static function (Application $app, array $config) {
            return new EHealthUserProvider($app['hash'], $config['model']);
        });
    }
}
