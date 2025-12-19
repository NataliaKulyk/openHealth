<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;

class Declaration extends Request
{
    protected const string URL = '/api/declarations';

    /**
     * Get shortened details about declarations.
     *
     * @param  string  $url
     * @param  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getMany(string $url = self::URL, $query = null): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        $mergedQuery = array_merge($this->options['query'], $query ?? []);

        return parent::get($url, $mergedQuery);
    }

    /**
     * Receive detailed information about person Declaration by declaration ID.
     *
     * @param  string  $url  Request identifier
     * @param  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function get(string $url, $query = null): PromiseInterface|EHealthResponse
    {
        return parent::get(self::URL . "/$url", $query);
    }
}
