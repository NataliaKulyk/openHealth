<?php

declare(strict_types=1);

namespace App\Providers;

use Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider;
use Illuminate\Bus\BatchRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Repositories\EHealthDatabaseBatchRepository;
use Illuminate\Bus\BatchFactory;
use Illuminate\Pagination\Paginator;

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

        /*
         * Extend BatchRepository to use EHealthDatabaseBatchRepository to allow store LegalEntity's ID into job_batches table
         * NOTE: don't remove '$_' this param. It will need to properly override the existing binding
         * $_ is a original DatabaseBatchRepository which code below trying to override (it don't use, so the name is just $_)
         */
        $this->app->extend(BatchRepository::class, function ($_, $app) {
            return new EHealthDatabaseBatchRepository(
                $app->make(BatchFactory::class),
                $app->make('db')->connection(),
                config('queue.batches.table', 'job_batches')
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale(config('app.locale'));

        Model::shouldBeStrict($this->app->isLocal());
        DB::prohibitDestructiveCommands($this->app->isProduction());

        RateLimiter::for('ehealth-employee-get', function (object $job) {
            echo "Rate limiter set for user: " . $job->user->id . PHP_EOL; // TODO: remove it after testing
            return Limit::perMinute(config('ehealth.rate_limit.employee_request'))->by($job->user->id);
        });

        RateLimiter::for('ehealth-division-get', function (object $job) {
            echo "Rate limiter set for user: " . $job->user->id . PHP_EOL; // TODO: remove it after testing
            return Limit::perMinute(config('ehealth.rate_limit.division_request'))->by($job->user->id);
        });

        // RateLimiter::for('ehealth-division-get', fn (object $job) => Limit::perMinute(50)->by($job->user->id));
    }
}
