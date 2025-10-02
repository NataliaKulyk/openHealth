<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;

class Person extends Request
{
    protected const string URL = '/api/persons';

    /**
     * Search for a person by parameters.
     *
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function searchForPersonByParams(array $query): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL, $query);
    }

    /**
     * Re-send SMS to a person who approve creating or updating data about himself.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getAuthMethods(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL . "/$id/authentication_methods", $query);
    }

    /**
     * Get current person's verification status and another information about it.
     *
     * @param  string  $id
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getPersonVerificationDetails(string $id): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL . "/$id/verification");
    }

    /**
     * Create new Confidant Person relationship request.
     *
     * @param  string  $id
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function createConfidantRelationship(string $id): PromiseInterface|EHealthResponse
    {
        return $this->post(self::URL . "/$id/confidant_person_relationship_requests");
    }

    /**
     * Get list of active confidant person relationships.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getConfidantPersonRelationships(string $id, array $query = []): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL . "/$id/confidant_person_relationships", $query);
    }

    /**
     * Adding an authentication method to an existing person, update authentication method and delete it.
     *
     * @param  string  $id
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function createAuthMethod(string $id, array $query): PromiseInterface|EHealthResponse
    {
        return $this->post(self::URL . "/$id/authentication_method_requests", $query);
    }

    /**
     * Re-send SMS to a person or third person.
     *
     * @param  string  $id
     * @param  string  $requestId
     * @param  array  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function resendAuthOtp(string $id, string $requestId, array $query = []): PromiseInterface|EHealthResponse
    {
        return $this->post(self::URL . "/$id/authentication_method_requests/$requestId/actions/resend_otp", $query);
    }
}
