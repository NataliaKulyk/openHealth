<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Rules\InDictionary;
use App\Rules\PhoneNumber;
use App\Rules\TaxId;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class Person extends Request
{
    protected const string URL = '/api/persons';
    protected const string URL_V2 = '/api/v2/persons';

    /**
     * Search for a person by parameters.
     *
     * @param  array  $params
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function searchForPersonByParams(array $params): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateSearch(...));

        return $this->get(self::URL, $params);
    }

    /**
     * This method allows to find all persons, which were merged with this person.
     * Also, this endpoint shows all the persons who enter the whole chain of merges to this person.
     *
     * @param  string  $uuid
     * @param  array  $params
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function searchPersonsMergedPersons(string $uuid, array $params = []): PromiseInterface|EHealthResponse
    {
        $this->setDefaultPageSize();

        return $this->get(self::URL . "/$uuid/merged_persons", $params);
    }

    /**
     * This method is used to obtain full information about person by ID. This method is applicable only if there is an active approval of type 'person'.
     *
     * @param  string  $uuid
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getPersonalData(string $uuid): PromiseInterface|EHealthResponse
    {
        return $this->get(self::URL . '/' . $uuid . '/personal_data');
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
        $this->setValidator($this->validateAuthMethods(...));

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

    protected function validateSearch(EHealthResponse $response): array
    {
        $data = $response->getData();

        $validator = Validator::make($data, [
            '*.birth_country' => ['required', 'string', 'max:255'],
            '*.birth_date' => ['required', 'date'],
            '*.birth_settlement' => ['required', 'string', 'max:255'],
            '*.first_name' => ['required', 'string', 'max:255'],
            '*.gender' => ['required', new InDictionary('GENDER')],
            '*.id' => ['required', 'uuid'],
            '*.last_name' => ['required', 'string', 'max:255'],
            '*.second_name' => ['nullable', 'string', 'max:255'],
            '*.phones' => ['nullable', 'array'],
            '*.phones.*.number' => ['required', new PhoneNumber()],
            '*.phones.*.type' => ['required', new InDictionary('PHONE_TYPE')],
            '*.tax_id' => ['nullable', new TaxId()]
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    protected function validateAuthMethods(EHealthResponse $response): array
    {
        $data = $response->getData();

        $replaced = self::replaceEHealthPropNames($data);

        $validator = Validator::make($replaced, [
            '*.alias' => ['nullable', 'string', 'max:255'],
            '*.ehealth_ended_at' => ['nullable', 'date'],
            '*.uuid' => ['required', 'uuid'],
            '*.type' => ['nullable', 'string', 'max:255'],
            '*.value' => ['nullable', 'uuid'],
            '*.phone_number' => ['nullable', 'string', 'max:255']
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    /**
     * Replace eHealth property names with the ones used in the application.
     * E.g., id => uuid.
     */
    protected static function replaceEHealthPropNames(array $properties): array
    {
        $replaced = [];

        foreach ($properties as $index => $item) {
            if (is_array($item)) {
                $replacedItem = [];
                foreach ($item as $key => $value) {
                    $newKey = match ($key) {
                        'id' => 'uuid',
                        'ended_at' => 'ehealth_ended_at',
                        default => $key
                    };
                    $replacedItem[$newKey] = $value;
                }
                $replaced[$index] = $replacedItem;
            } else {
                $replaced[$index] = $item;
            }
        }

        return $replaced;
    }
}
