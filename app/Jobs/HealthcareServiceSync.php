<?php

namespace App\Jobs;

use App\Core\EHealthJob;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Classes\eHealth\EHealth;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use Illuminate\Queue\Middleware\RateLimited;

class HealthcareServiceSync extends EHealthJob
{
    public const string BATCH_NAME = 'HealthcareServiceSync';

    public const string SCOPE_REQUIRED = 'healthcare_service:read';

    public const string ENTITY = LegalEntity::ENTITY_HEALTHCARE_SERVICE;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        echo 'Starting HealthcareService Sync for user:' . $this->user->id . ', legalEntity:' . $this->legalEntity->id . ', page:' . $this->page . PHP_EOL;

        parent::handle();
    }

    // Get data from EHealth API
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::healthcareService()
                ->withToken($token)
                ->getMany(query: ['page' => $this->page], groupByEntities: true);
    }

    // Store or update data in the database
    protected function processResponse(?EHealthResponse $response): void
    {
        $healthcareServicesData = $response?->validate();

        if (empty($healthcareServicesData)) {
            return;
        }

        Repository::healthcareService()->saveHealthcareServiceAll($healthcareServicesData['healthcare_services'], $healthcareServicesData['divisions']);
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
