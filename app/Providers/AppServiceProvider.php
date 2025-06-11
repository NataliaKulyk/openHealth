<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\LegalEntity;
use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->isLocal()) {
            $this->app->register(IdeHelperServiceProvider::class);
        }

        if (!$this->app->runningInConsole()) {
            $this->app->singletonIf(LegalEntity::class, fn () => Auth::user()->legalEntity);
            $this->app->alias(LegalEntity::class, 'legalEntity');
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));
        Model::shouldBeStrict($this->app->isLocal());
        DB::prohibitDestructiveCommands($this->app->isProduction());
    }
}
