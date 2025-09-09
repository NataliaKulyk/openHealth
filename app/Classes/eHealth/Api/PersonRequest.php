<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;

class PersonRequest extends Request
{
    protected const string URL = '/api/person_requests';
    protected const string URL_V2 = '/api/v2/person_requests';

    /**
     * Create Person Request v2 (as part of Person creation w/o declaration process).
     *
     * @param  string  $url
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function create(string $url = self::URL_V2, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->post($url, $data);
    }

    /**
     * Approve previously created Person Request v2.
     *
     * @param  string  $id
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function approve(string $id, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->patch(self::URL_V2 . "/$id/actions/approve", $data ?: (object)$data);
    }

    /**
     * Reject previously created Person Request v2.
     *
     * @param  string  $id
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function reject(string $id): PromiseInterface|EHealthResponse
    {
        return $this->patch(self::URL_V2 . "/$id/actions/reject");
    }

    /**
     * Sign approved previously created Person Request v2.
     *
     * @param  string  $id
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function signed(string $id, array $data): PromiseInterface|EHealthResponse
    {
        return $this->patch(self::URL_V2 . "/$id/actions/sign", $data);
    }

    /**
     * Obtains details by ID.
     *
     * @param  string  $id
     * @param  null  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getById(string $id, $query = null): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL_V2 . "/$id", $query);
    }

    /**
     * Obtains details by setting parameters like status, page, and page size.
     *
     * @param  string  $url
     * @param  null  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getList(string $url = self::URL, $query = null): PromiseInterface|EHealthResponse
    {
        return $this->get($url, $query);
    }

    /**
     * Re-send SMS to a person who approve creating or updating data about himself.
     *
     * @param  string  $id
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function resendAuthOtp(string $id, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->post(self::URL . "/$id/actions/resend_otp", $data);
    }
}
