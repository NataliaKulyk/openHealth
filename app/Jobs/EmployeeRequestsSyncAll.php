<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Models\LegalEntity;
use App\Services\Employee\EmployeeRequestProcessor;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;

class EmployeeRequestsSyncAll extends EHealthJob
{
    public const string BATCH_NAME = 'EmployeeRequestsSyncAll';
    public const string SCOPE_REQUIRED = 'employee_request:read';
    public const string ENTITY = LegalEntity::ENTITY_EMPLOYEE;

    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        Log::info('[EmployeeRequestsSyncAll] Sending request for page ' . $this->page);

        return EHealth::employeeRequest()
            ->withToken($token)
            ->getMany(['edrpou' => $this->legalEntity->edrpou], $this->page);
    }

    protected function processResponse(?EHealthResponse $response): void
    {
        Log::info('[EmployeeRequestsSyncAll] Processing page ' . $this->page);

        $validatedData = $response?->validate() ?? [];

        // Use the service to process the data
        $processor = app(EmployeeRequestProcessor::class);
        $processor->processBatch($validatedData, $this->legalEntity);
    }

    protected function getAdditionalMiddleware(): array
    {
        return [new RateLimited('ehealth-employee-request-get')];
    }
}
