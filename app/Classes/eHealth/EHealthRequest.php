<?php

declare(strict_types=1);

namespace App\Classes\eHealth;

use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\HigherOrderTapProxy;

abstract class EHealthRequest extends PendingRequest
{
    public function __construct(?Factory $factory = null, $middleware = [])
    {
        parent::__construct($factory, $middleware);

        $this->withHeaders([
            'X-Custom-PSK' => config('ehealth.api.token'),
            'API-key' => config('ehealth.api.api_key'),
        ]);

        if (eHealthToken()->hasBearerToken()) {
            $this->withToken(eHealthToken()->getBearerToken());
        }

        $this->baseUrl(
            config('ehealth.api.domain')
        );
    }

    /**
     * Override this method from the parent class to get validated data from the response.
     * @return array validated data
     */
    public function validate(): array
    {
        return [];
    }

    /**
     * Overrides the HTTP Client Request method to get a custom response.
     */
    protected function newResponse($response): HigherOrderTapProxy|EHealthResponse
    {
        return tap(new EHealthResponse($response), function (EHealthResponse $laravelResponse) {
            if ($this->truncateExceptionsAt === null) {
                return;
            }

            $this->truncateExceptionsAt === false
                ? $laravelResponse->dontTruncateExceptions()
                : $laravelResponse->truncateExceptionsAt($this->truncateExceptionsAt);
        });
    }
}
