<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest as Request;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Rules\InDictionary;
use App\Models\LegalEntity as LegalEntityModel;
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
     * @param  $query
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function getMany(string $url = self::URL, $query = null): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));
        $this->setMapper($this->mapMany(...));
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
        $this->setValidator($this->validateResponse(...));
        $this->setMapper($this->mapResponse(...));

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
        $this->setValidator($this->validateResponse(...));
        $this->setMapper($this->mapResponse(...));

        return $this->patch(self::URL . '/' . $uuid, $data);
    }

    /**
     * Validate healthcare service response (create, activate, deactivate),
     * see: https://uaehealthapi.docs.apiary.io/#reference/public.-medical-service-provider-integration-layer/healthcare-services/create-healthcare-service
     */
    protected function validateResponse(EHealthResponse $response): array
    {
        $data = $response->getData();

        $replaced = self::replaceEHealthPropNames($data);

        $validator = Validator::make($replaced, $this->validationRules());

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
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

        // Add *. to every rule
        $rules = collect($this->validationRules())
            ->mapWithKeys(static fn (string|array $rule, string $key) => ["*.$key" => $rule])
            ->toArray();

        $validator = Validator::make($replaced, $rules);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    /**
     * List of validation rules for healthcare service.
     *
     * @return array
     */
    protected function validationRules(): array
    {
        return [
            'active_from_date' => 'required|date_format:Y-m-d',
            'expiry_date' => 'required|date_format:Y-m-d',
            'uuid' => 'required|uuid',
            'is_active' => 'required|boolean',
            'is_primary' => 'required|boolean',
            'issued_by' => 'required|string',
            'issued_date' => 'required|date_format:Y-m-d',
            'issuer_status' => 'sometimes|string|nullable',
            'legal_entity_id' => ['required', 'uuid', Rule::in([legalEntity()->uuid])],
            'license_number' => 'required|string',
            'order_no' => 'required|string',
            'type' => ['required', 'string', new InDictionary('LICENSE_TYPE')],
            'what_licensed' => 'required|string',
            'ehealth_inserted_at' => 'required|date',
            'ehealth_inserted_by' => 'required|uuid',
            'ehealth_updated_at' => 'required|date',
            'ehealth_updated_by' => 'required|uuid'
        ];
    }

    /**
     * Map UUID values to ID.
     *
     * @param  array  $validated
     * @return array
     */
    protected function mapResponse(array $validated): array
    {
        $validated['legal_entity_id'] = LegalEntityModel::where('uuid', $validated['legal_entity_id'])->value('id');

        return $validated;
    }

    /**
     * Map UUID values to ID for multiple records.
     *
     * @param  array  $validated
     * @return array
     */
    protected function mapMany(array $validated): array
    {
        // Get unique uuids.
        $legalEntityUuids = collect($validated)->pluck('legal_entity_id')->unique()->filter()->values();

        $legalEntityMap = LegalEntityModel::whereIn('uuid', $legalEntityUuids)
            ->pluck('id', 'uuid')
            ->toArray();

        // Map uuid to id
        return collect($validated)->map(static function (array $item) use ($legalEntityMap) {
            $item['legal_entity_id'] = $legalEntityMap[$item['legal_entity_id']];

            return $item;
        })->toArray();
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
