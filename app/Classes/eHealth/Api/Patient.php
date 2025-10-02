<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;

class Patient extends Request
{
    protected const string URL = '/api/patients';

    /**
     * Get brief information about episodes, in order not to disclose confidential and sensitive data.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getShortEpisodes(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        return $this->get(self::URL . "/$id/summary/episodes", $query);
    }

    /**
     * Get the current diagnoses related only to active episodes.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getActiveDiagnoses(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        return $this->get(self::URL . "/$id/summary/diagnoses", $query);
    }

    /**
     * Get the current diagnoses related only to active episodes.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getObservations(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        return $this->get(self::URL . "/$id/summary/observations", $query);
    }
}
