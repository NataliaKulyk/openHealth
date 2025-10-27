<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Classes\eHealth\EHealth;
use App\Events\EHealthUserLogin;
use App\Jobs\PartyVerificationSync;
use App\Notifications\SyncNotification;
use App\Traits\ProcessesPartyVerificationResponses;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Throwable;

class PartyVerificationSyncStatusOnLogin
{
    use ProcessesPartyVerificationResponses;

    /**
     * Handle the event using the hybrid sync pattern.
     */
    public function handle(EHealthUserLogin $event): void
    {
        Log::info('Listener ' . self::class . ' is executing for User ID: ' . $event->user->id);

        $user = $event->user;
        $legalEntity = $event->legalEntity;

        try {
            $token = Crypt::decryptString($event->token);

            $response = EHealth::party()->getMany($token);

            $this->processPartyVerificationResponse($response, $legalEntity);

            if ($response->isNotLast()) {
                Bus::batch([new PartyVerificationSync($legalEntity, null, false, 2)])
                    ->name('Party Verification Status Sync')
                    ->withOption('legal_entity_id', $legalEntity->id)
                    ->withOption('token', $event->token)
                    ->withOption('user', $user)
                    ->then(function(Batch $batch) use ($user) {
                        $user->notify(new SyncNotification('party_verification', 'completed'));
                        Log::info('Batch [Party Verification Status Sync] completed.', ['id' => $batch->id]);
                    })
                    ->catch(function(Batch $batch, Throwable $e) use ($user) {
                        $user->notify(new SyncNotification('party_verification', 'failed'));
                        Log::error('Batch [Party Verification Status Sync] failed.', ['id' => $batch->id, 'error' => $e->getMessage()]);
                    })
                    ->onQueue('sync')
                    ->dispatch();

                $user->notify(new SyncNotification('party_verification', 'started'));
            }

        } catch (Throwable $e) {
            Log::error('Failed to start party verification sync on login.', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

}
