<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Rules\InDictionary;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class License extends Request
{
    protected const string URL = '/api/licenses';

    /**
     * Use this end-point to obtain all Licenses of the legal entity.
     *
     * @param  string  $url
     * @param $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getMany(string $url = self::URL, $query = null): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));
        $this->setDefaultPageSize();

        return $this->get($url, $query);
    }

    /**
     * This method must be used to create additional licenses for legal entity.
     *
     * @param  string  $url
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function create(string $url = self::URL, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->post($url, $data);
    }

    /**
     * This method must be used to update additional license for legal entity.
     *
     * @param  string  $uuid  unique eHealth identifier of the license
     * @param  array  $data
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function update(string $uuid, array $data = []): PromiseInterface|EHealthResponse
    {
        return $this->patch(self::URL . '/' . $uuid, $data);
    }

    /**
     * validate get licenses input,
     * see: https://esoz.docs.apiary.io/#reference/administration/get-licenses
     */
    protected function validateMany(EHealthResponse $response): array
    {
        $replaced = [];
        foreach ($response->getData() as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $validator = Validator::make($replaced, [
            '*' => 'required|array',
            '*.active_from_date' => 'required|date_format:Y-m-d',
            '*.expiry_date' => 'required|date_format:Y-m-d',
            '*.uuid' => 'required|uuid',
            '*.is_active' => 'required|boolean',
            '*.is_primary' => 'required|boolean',
            '*.issued_by' => 'required|string',
            '*.issued_date' => 'required|date_format:Y-m-d',
            '*.issuer_status' => 'sometimes|string|nullable',
            '*.legal_entity_uuid' => ['required', 'uuid', Rule::in([legalEntity()->uuid])],
            '*.license_number' => 'required|string',
            '*.order_no' => 'required|string',
            '*.type' => ['required', 'string', new InDictionary('LICENSE_TYPE')],
            '*.what_licensed' => 'required|string',
            '*.ehealth_inserted_at' => 'required|date',
            '*.ehealth_inserted_by' => 'required|uuid',
            '*.ehealth_updated_at' => 'required|date',
            '*.ehealth_updated_by' => 'required|uuid',
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

        foreach ($properties as $name => $value) {
            switch ($name) {
                case 'id':
                    $replaced['uuid'] = $value;
                    break;
                case 'legal_entity_id':
                    $replaced['legal_entity_uuid'] = $value;
                    break;
                case 'inserted_at':
                    $replaced['ehealth_inserted_at'] = $value;
                    break;
                case 'inserted_by':
                    $replaced['ehealth_inserted_by'] = $value;
                    break;
                case 'updated_at':
                    $replaced['ehealth_updated_at'] = $value;
                    break;
                case 'updated_by':
                    $replaced['ehealth_updated_by'] = $value;
                    break;
                default:
                    $replaced[$name] = $value;
                    break;
            }
        }

        return $replaced;
    }
}
