<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Enums\Equipment\Status;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity as LegalEntityModel;
use App\Models\Division as DivisionModel;
use App\Models\Equipment as EquipmentModel;
use App\Models\Employee\Employee as EmployeeModel;
use App\Rules\InDictionary;
use Illuminate\Support\Facades\Log;
use App\Classes\eHealth\EHealthResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Client\ConnectionException;
use App\Classes\eHealth\EHealthRequest as Request;
use Illuminate\Validation\Rule;

class Equipment extends Request
{
    protected const string URL = '/api/equipment';

    /**
     * Create the Healthcare Service.
     *
     * @param  array  $data  // Data for API request
     * @return EHealthResponse|PromiseInterface
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function create(array $data = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateResponse(...));
        $this->setMapper($this->mapCreate(...));

        return $this->post(self::URL, $data);
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
     * Map UUID values to ID.
     *
     * @param  array  $validated
     * @return array
     */
    protected function mapCreate(array $validated): array
    {
        if (isset($validated['division_id'])) {
            $validated['division_id'] = DivisionModel::where('uuid', $validated['division_id'])->value('id');
        }

        if (isset($validated['parent_id'])) {
            $validated['parent_id'] = EquipmentModel::where('uuid', $validated['parent_id'])->value('id');
        }

        $validated['legal_entity_id'] = LegalEntityModel::where('uuid', $validated['legal_entity_id'])->value('id');
        $validated['recorder'] = EmployeeModel::where('uuid', $validated['recorder'])->value('id');

        return $validated;
    }

    /**
     * List of validation rules for healthcare service.
     *
     * @return array
     */
    protected function validationRules(): array
    {
        return [
            'availability_status' => ['required', 'string', new InDictionary('equipment_availability_statuses')],
            'device_definition_id' => ['nullable', 'uuid'],
            'division_id' => ['nullable', 'uuid', 'exists:divisions,uuid'],
            'error_reason' => ['nullable', 'string', 'max:255'],
            'expiration_date' => ['nullable', 'date'],
            'uuid' => ['required', 'uuid'],
            'ehealth_inserted_at' => ['required', 'date'],
            'ehealth_inserted_by' => ['required', 'uuid'],
            'inventory_number' => ['nullable', 'string', 'max:255'],
            'legal_entity_id' => ['required', 'uuid', 'exists:legal_entities,uuid'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'manufacture_date' => ['nullable', 'date'],
            'manufacturer' => ['nullable', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:255'],
            'names' => ['required', 'array'],
            'names.*.name' => ['required', 'string', 'max:255'],
            'names.*.type' => ['required', 'string', new InDictionary('device_name_type')],
            'note' => ['nullable', 'string', 'max:1000'],
            'parent_id' => ['nullable', 'uuid', 'exists:equipments,uuid'],
            'properties' => ['nullable', 'array'],
            'properties.*.type' => ['required', 'string', 'max:255', new InDictionary('device_properties')],
            'properties.*.valueInteger' => ['nullable', 'integer:strict'],
            'properties.*.valueDecimal' => ['nullable', 'decimal'],
            'properties.*.valueBoolean' => ['nullable', 'boolean:strict'],
            'properties.*.valueString' => ['nullable', 'string', 'max:255'],
            'recorder' => ['required', 'uuid', 'exists:employees,uuid'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in(Status::ACTIVE)],
            'type' => ['required', 'string', 'max:255', new InDictionary('device_definition_classification_type')],
            'ehealth_updated_at' => ['required', 'date'],
            'ehealth_updated_by' => ['required', 'uuid']
        ];
    }

    /**
     * Replace eHealth property names with the ones used in the application.
     * E.g., id => uuid.
     */
    protected static function replaceEHealthPropNames(array $properties): array
    {
        $replaced = [];

        foreach ($properties as $name => $value) {
            $newName = match ($name) {
                'id' => 'uuid',
                'inserted_at' => 'ehealth_inserted_at',
                'inserted_by' => 'ehealth_inserted_by',
                'updated_at' => 'ehealth_updated_at',
                'updated_by' => 'ehealth_updated_by',
                default => $name
            };

            $replaced[$newName] = $value;
        }

        return $replaced;
    }
}
