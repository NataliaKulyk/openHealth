<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Events\EHealthUserLogin;
use App\Jobs\EmployeeRequestsSyncAll;
use App\Jobs\EmployeeRequestsSyncUser;
use App\Notifications\SyncNotification;
use Cache;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

class EmployeeUpdate
{
    /**
     * Handle the EHealthUserLogin event.
     *
     * @param  EHealthUserLogin  $event  The event containing user and legal entity context.
     * @return void
     * @throws Throwable
     */
    public function handle(EHealthUserLogin $event): void
    {
        $legalEntity = $event->legalEntity;
        $user = $event->user;

        Log::info('[EmployeeUpdate] Executing SYNC personal update for user: ' . $user->id);

        EmployeeRequestsSyncUser::dispatchSync(
            $legalEntity,
            $user,
            $event->token
        );

        if ($user->can('employee_request:read')) {
            $cacheKey = 'employee_request_sync_ran_for_' . $legalEntity->id . '_' . now()->toDateString();

            if (Cache::has($cacheKey)) {
                Log::info('[EmployeeUpdate] Daily full sync has already run. Skipping.');
                return;
            }

            Cache::put($cacheKey, true, now()->endOfDay());

            Log::info('[EmployeeUpdate] Dispatching daily FULL sync (queued) for employee requests.');

            Bus::batch([
                           new EmployeeRequestsSyncAll($legalEntity)
                       ])
                ->name('Full Employee Requests Sync for LE: ' . $legalEntity->id)
                ->withOption('legal_entity_id', $legalEntity->id)
                ->withOption('token', $event->token)
                ->withOption('user', $user)
                ->then(function () use ($user) {
                    $user->notify(new SyncNotification('employee_request_full_sync', 'completed'));
                })
                ->catch(function (Batch $batch, Throwable $e) use ($user) {
                    Log::error('Batch [Full Employee Requests Sync] failed.', [
                        'batch_id' => $batch->id,
                        'error' => $e->getMessage()
                    ]);
                    $user->notify(new SyncNotification('employee_request_full_sync', 'failed'));
                })
                ->onQueue('sync')
                ->dispatch();

            $user->notify(new SyncNotification('employee_request_full_sync', 'started'));
        }
    }
}
