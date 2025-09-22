<?php

namespace App\Jobs;

use App\Core\EHealthJob;
use App\Notifications\SyncNotification;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;

/**
 * This job is responsible for finalizing a full synchronization operation between different data sources
 *
 * @package App\Jobs
 */
class CompleteSync extends EHealthJob
{
    public const string BATCH_NAME = 'CompleteSync';

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        echo 'Starting CompleteSync for legalEntity : ' . $this->legalEntity->id . PHP_EOL;

        parent::handle();

        $this->user->notify(new SyncNotification('legal_entity', 'completed'));
    }

    protected function sendRequest(string $token): PromiseInterface|EHealthResponse|null
    {
        return null;
    }

    protected function processResponse(?EHealthResponse $response): void
    {
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [];
    }
}
