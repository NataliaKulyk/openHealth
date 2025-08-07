<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;

class Person extends Request
{
    protected const string URL = '/api/persons';

    /**
     * Re-send SMS to a person who approve creating or updating data about himself.
     *
     * @param  string  $id
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getAuthMethods(string $id, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL . "/$id/authentication_methods", $data);
    }
}
