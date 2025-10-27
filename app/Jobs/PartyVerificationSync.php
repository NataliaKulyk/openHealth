<?php

namespace App\Jobs;

use App\Classes\eHealth\EHealthResponse;
use App\Core\EHealthJob;
use App\Classes\eHealth\EHealth;
use App\Models\Relations\Party;
use App\Notifications\PartyVerificationStatusChanged;
use App\Traits\BatchLegalEntityQueries;
use App\Traits\ProcessesPartyVerificationResponses;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;

class PartyVerificationSync extends EHealthJob
{
    use BatchLegalEntityQueries, ProcessesPartyVerificationResponses;

    public const string BATCH_NAME = 'PartyVerificationFullSync';
    public const string SCOPE_REQUIRED = 'party:read';
    public const string ENTITY = 'party_verification';

    /**
     * @throws ConnectionException
     */
    protected function sendRequest(string $token): PromiseInterface|EHealthResponse
    {
        return EHealth::party()->getMany($token, [], $this->page);
    }

    protected function processResponse(?EHealthResponse $response): void
    {
        $this->processPartyVerificationResponse($response, $this->legalEntity);
    }

    protected function getAdditionalMiddleware(): array
    {
        return [new RateLimited('ehealth-party-verification-get')];
    }
}
