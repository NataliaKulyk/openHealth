<?php

namespace App\Jobs;

use App\Core\EHealthJob;
use App\Models\LegalEntity;
use App\Classes\eHealth\EHealth;
use App\Repositories\Repository;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use Illuminate\Queue\Middleware\RateLimited;

class DivisionSync extends EHealthJob
{
    public const string BATCH_NAME = 'DivisionSync';

    public const string SCOPE_REQUIRED = 'division:read';

    public const string ENTITY = LegalEntity::ENTITY_DIVISION;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        echo 'Starting DivisionSync for user:' . $this->user->id . ', legalEntity:' . $this->legalEntity->id . ', page:' . $this->page . PHP_EOL;

        parent::handle();
    }

    // Get data from EHealth API
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::division()
                ->withToken($token)
                ->getMany(query: ['page' => $this->page]);
    }

    // Store or update data in the database
    protected function processResponse(?EHealthResponse $response): void
    {
        $divisionsList = $response?->validate();

        if (empty($divisionsList)) {
            return;
        }

        Repository::division()->saveDivisionsList($divisionsList, $this->legalEntity);
    }

    /**
     * Get additional middleware configurations for the job.
     *
     * @return array Returns an array of middleware configurations to be applied to the job
     */
    protected function getAdditionalMiddleware(): array
    {
        return [
            new RateLimited('ehealth-division-get')
        ];
    }
}
