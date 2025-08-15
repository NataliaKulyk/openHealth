<?php

namespace App\Classes\eHealth\Api;

use Arr;
use Exception;
use Illuminate\Validation\Rule;
use App\Traits\WorkTimeUtilities;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Promise\PromiseInterface;
use App\Classes\eHealth\EHealthResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\ConnectionException;
use App\Classes\eHealth\EHealthRequest as Request;

class Division extends Request
{
    use WorkTimeUtilities;

    public const string URL = '/api/divisions';

    public const string ACTIONS_ACTIVATE = '/actions/activate';

    public const string ACTIONS_DEACTIVATE = '/actions/deactivate';

    /**
     * Get list of Divisisons belong to the current LegalEntity
     *
     * Important: If the second parameter `$query` is provided,
     * it will override any previously set query parameters (e.g., via `withQueryParameters()`).
     *
     * If only the URL is provided (i.e., one argument) or nothing, the request will use the internal `$this->options`.
     *
     * @param string $url The request URL.
     * @param array|null $query Optional query parameters. If provided, it replaces any existing 'query' options.
     *
     * @return PromiseInterface|EHealthResponse
     */
    public function getMany(string $url = self::URL, $query = null): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));

        $this->setDefaultPageSize();

        $mergedQuery = array_merge(
    $this->options['query'] ?? [],
            $query ?? []
        );

        return parent::get($url, $mergedQuery);
    }

    /**
     * Update the Division
     *
     * @param string $uuid
     * @param mixed $data // Data for API request
     *
     * @return EHealthResponse|PromiseInterface
     */
    public function update(string $uuid, $data = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateOne(...));

        $mappedData = $this->mapRequest($data);

        return parent::patch(self::URL . '/' . $uuid, $mappedData);
    }

    /**
     * Create the Division
     *
     * @param mixed $data // Data for API request
     *
     * @return EHealthResponse|PromiseInterface
     */
    public function create($data = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateOne(...));

        $mappedData = $this->mapRequest($data);

        return parent::post(self::URL, $mappedData);
    }

    /**
     * @param string $uuid unique eHealth identifier of the license
     * @param array $data
     *
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function activate(string $uuid): PromiseInterface|EHealthResponse
    {
        return parent::patch(self::URL . '/' . $uuid . self::ACTIONS_ACTIVATE);
    }

    /**
     * @param string $uuid unique eHealth identifier of the license
     * @param array $data
     *
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function deactivate(string $uuid): PromiseInterface|EHealthResponse
    {
        return parent::patch(self::URL . '/' . $uuid . self::ACTIONS_DEACTIVATE);
    }

    /**
     * validate get Divisions input,
     * see: https://esoz.docs.apiary.io/#reference/administration/divisions/get-divisions
     */
    protected function validateMany(EHealthResponse $response): array
    {
        if (! $response->successful()) {
            throw new Exception('validateMany: ' . $response->body());
        }

        $replaced = [];

        $divisionsList = $response->getData();

        $validationRules = ['*' => 'required|array'];

        foreach ($this->getValidationRules() as $key => $rule) {
            $validationRules["*.{$key}"] = $rule;
        }

        foreach ($divisionsList as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $validator = Validator::make($replaced, $validationRules);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    /**
     * Validate single division response data
     * see; https://uaehealthapi.docs.apiary.io/#reference/public.-medical-service-provider-integration-layer/divisions/get-division-details
     */
    protected function validateOne(EHealthResponse $response): array
    {
        if (! $response->successful()) {
            throw new Exception('validateOne: ' . $response->body());
        }

        $data = $response->getData();

        $replaced = self::replaceEHealthPropNames($data);

        $validator = Validator::make($replaced, $this->getValidationRules());

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    /**
     * Returns the validation rules array used for validating healthcare service data.
     *
     * @return array An associative array containing validation rules for healthcare service fields
     */
    protected function getValidationRules(): array
    {
        return [
            'addresses' => 'required|array',
            'addresses.*.apartment' => 'nullable|string',
            'addresses.*.area' => 'required|string',
            'addresses.*.building' => 'nullable|string',
            'addresses.*.country' => 'required|string',
            'addresses.*.region' => 'nullable|string',
            'addresses.*.settlement' => 'required|string',
            'addresses.*.settlement_id' => 'required|string',
            'addresses.*.settlement_type' => 'required|string',
            'addresses.*.street' => 'nullable|string',
            'addresses.*.street_type' => 'nullable|string',
            'addresses.*.type' => 'required|string',
            'addresses.*.zip' => 'nullable|string',
            'uuid' => 'required|uuid',
            'email' => 'required|string',
            'external_id' => 'nullable|string',
            'legal_entity_uuid' => ['required', 'uuid', Rule::in([legalEntity()->uuid])],
            'location' => 'nullable|array',
            'location.latitude' => 'required_with:location|numeric',
            'location.longitude' => 'required_with:location|numeric',
            'mountain_group' => 'sometimes|boolean',
            'name' => 'required|string',
            'phones' => 'required|array',
            'phones.*.number' => 'required|string',
            'phones.*.type' => 'required|string',
            'phones.*.note' => 'sometimes|string',
            'status' => 'required|string',
            'type' => 'required|string',
            'working_hours' => 'nullable|array',
            'working_hours.sun' => 'nullable|array',
            'working_hours.sun.*.0' => 'required|string',
            'working_hours.sun.*.1' => 'required|string',
            'working_hours.mon' => 'nullable|array',
            'working_hours.mon.*.0' => 'required|string',
            'working_hours.mon.*.1' => 'required|string',
            'working_hours.tue' => 'nullable|array',
            'working_hours.tue.*.0' => 'required|string',
            'working_hours.tue.*.1' => 'required|string',
            'working_hours.wed' => 'nullable|array',
            'working_hours.wed.*.0' => 'required|string',
            'working_hours.wed.*.1' => 'required|string',
            'working_hours.thu' => 'nullable|array',
            'working_hours.thu.*.0' => 'required|string',
            'working_hours.thu.*.1' => 'required|string',
            'working_hours.fri' => 'nullable|array',
            'working_hours.fri.*.0' => 'required|string',
            'working_hours.fri.*.1' => 'required|string',
            'working_hours.sat' => 'nullable|array',
            'working_hours.sat.*.0' => 'required|string',
            'working_hours.sat.*.1' => 'required|string',
        ];
    }

    protected function schemaRequest(): array
    {
        return [
            'working_hours' => ['nonEmpty' => true, 'format' => 'array', 'default' => [["00.00", "00.00"]]],
            'phones' => ['format' => 'array'],
            'location' => ['nonEmpty' => true, 'default' => ['longitude' => 0, 'latitude' => 0]],
            'name',
            'email',
            'type',
            'external_id',
            'addresses' => ['format' => 'array']
        ];
    }

    protected function mapRequest(array $requestData): array
    {
        $mapped = [];

        foreach ($this->schemaRequest() as $sourceKey => $rules) {
            // If specified the short form the $rules will be string, not an array
            if (is_int($sourceKey) && is_string($rules)) {
                $sourceKey = $rules;
                $rules = [];
            }

            if (!isset($requestData[$sourceKey]) || empty($requestData[$sourceKey])) {
                if (! Arr::boolean($rules, 'nonEmpty', false)) {
                    continue; // skip empty if allowed to
                }
            }

            // TODO: remove it for custom LocaltionCast
            // If collect here is empty thus the value of array means is EMPTY
            if (
                Arr::boolean($rules, 'nonEmpty', false) &&
                collect(Arr::wrap($requestData[$sourceKey] ?? []))->every(fn($item) => empty($item))
            ) {
                switch($sourceKey) {
                    case 'location':
                        $mapped['location'] = $rules['default'];
                        continue 2;
                }
            }

            // TODO: remove it for custom WorkingHoursCast and LocaltionCast accordingly
            switch ($sourceKey) {
            case 'location':
                $mapped['location'] = $this->reformatLocation($requestData['location']);

                continue 2;
            case 'working_hours':
                if (!empty($requestData['working_hours'])) {
                    $mapped['working_hours'] = $this->prepareWorkingHours($requestData['working_hours']);
                }
                else {
                    foreach(array_keys($this->weekdays) as $day) {
                        $mapped['working_hours'][$day] = $rules['default'];
                    }
                }

                continue 2;
            }

            $mapped[$sourceKey] = $requestData[$sourceKey];

            if (Arr::get($rules, 'format') === 'array') {
                $mapped[$sourceKey] = [$mapped[$sourceKey]];
            }
        }

        return $mapped;
    }

    /**
     * TODO: remove this after creating LocaltionCast
     * API service requires that values of longitude and latitude should be as number
     * but input fields returns string. Thus this values must be converted to.
     * Otherwise to save Location data comes from API-service the data should be JSON object,
     * therefore it must be converted too.
     *
     * @param array $location // Contains longitude and latitude values
     * @param bool $toJson    // Indicates when location data should be converted to teh JSON object
     *
     * @return string|array
     */
    protected function reformatLocation(array $location, bool $toJson = false): string|array
    {
        if($location && $toJson) {
            return json_encode($location);
        } else {
            $location['longitude'] = (float)$location['longitude'];
            $location['latitude'] = (float)$location['latitude'];

            return $location;
        }
    }

    /**
     * TODO: remove this after creating WorkingHoursCast
     * Change divider between hours and minutes
     *
     * @param array $workingHours   // Array with work hours time data
     * @param bool $dotToColon      // Determine how divider must be switched
     *
     * @return array
     */
    protected function prepareWorkingHours(array $workingHours, bool $dotToColon = false): array
    {
        return $this->prepareTimeToRequest($workingHours, $dotToColon);
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
                default:
                    $replaced[$name] = $value;
                    break;
            }
        }

        return $replaced;
    }
}
