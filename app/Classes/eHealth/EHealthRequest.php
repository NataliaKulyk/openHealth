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

    }

    protected function newResponse($response): HigherOrderTapProxy|Response|EHealthResponse
    {
        return tap(new EHealthResponse($response), function (Response $laravelResponse) {
            if ($this->truncateExceptionsAt === null) {
                return;
            }

            $this->truncateExceptionsAt === false
                ? $laravelResponse->dontTruncateExceptions()
                : $laravelResponse->truncateExceptionsAt($this->truncateExceptionsAt);
        });
    }
}
