<?php

namespace App\Listeners;

use Throwable;
use App\Enums\JobStatus;
use App\Jobs\CompleteSync;
use App\Jobs\DivisionSync;
use App\Events\EHealthUserLogin;
use App\Jobs\HealthcareServiceSync;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Bus;
use App\Notifications\SyncNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class FirstLoginOwnerSyncronization implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * This listener will be placed on the 'sync' queue
     *
     * @var string|null
     */
    public $queue = 'sync';

    /**
     * Handle the event.
     */
    public function handle(EHealthUserLogin $event): void
    {
        if (!$event->isFirstLogin) {
            return;
        }

        // TODO: remove it after testing
        echo 'First login synchronization started. ' . 'legalEntity:' . $event->legalEntity->id. PHP_EOL;

        $completeSyncJob = new CompleteSync($event->legalEntity, isFirstLogin: true);

        $healthcareServiceJob = new HealthcareServiceSync($event->legalEntity, isFirstLogin: true, nextEntity: $completeSyncJob);

        $initialJob = new DivisionSync( $event->legalEntity, isFirstLogin: true, nextEntity: $healthcareServiceJob);

        Bus::batch([$initialJob])
            ->name('FirstLoginSync')
            ->withOption('legal_entity_id', $event->legalEntity->id)
            ->withOption('token', $event->token) // Here token is encrypted
            ->withOption('user', $event->user)
            ->onQueue('sync')
            ->dispatch();

        $event->legalEntity->setEntityStatus(JobStatus::PROCESSING);
        $event->user->notify(new SyncNotification('legal_entity', 'started'));
    }

    /**
     * Handle a job failure.
     *
     * @param EHealthUserLogin $event
     * @param Throwable $exception
     * @return void
     */
    public function failed(EHealthUserLogin $event, Throwable $exception): void
    {
        $errorMessage = "FirstLoginOwnerSyncronization failed for legal entity ID: {$event->legalEntity->id}";
        $errorDetails = "Error: {$exception->getMessage()}";

        // Log the error
        Log::error($errorMessage, [
            'legal_entity_id' => $event->legalEntity->id,
            'error_message' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'listener' => self::class,
        ]);

        // Output to console
        echo $errorMessage . PHP_EOL;
        echo $errorDetails . PHP_EOL;
        echo "Stack trace: " . $exception->getTraceAsString() . PHP_EOL;
    }
}
