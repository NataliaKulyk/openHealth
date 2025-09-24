<?php

declare(strict_types=1);

namespace App\Livewire\Division\Forms;

use App\Enums\License\Type;
use App\Enums\Status;
use App\Models\Division;
use App\Models\HealthcareService;
use App\Rules\DivisionRules\HealthcareRules\CategoryInPharmacyRule;
use App\Rules\DivisionRules\HealthcareRules\CategoryRule;
use App\Rules\DivisionRules\HealthcareRules\LicenseRule;
use App\Rules\DivisionRules\HealthcareRules\NotAvailableTimeRule;
use App\Rules\InDictionary;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Exceptions\CustomValidationException;
use App\Rules\DivisionRules\HealthcareRules\AvailableTimeRule;
use App\Rules\DivisionRules\HealthcareRules\ProvidingConditionRule;
use Livewire\Form;

class HealthcareServiceForm extends Form
{
    public string $divisionId;

    public array $category = [
        'coding' => [['system' => 'HEALTHCARE_SERVICE_CATEGORIES']]
    ];

    public string $specialityType = '';

    public string $providingCondition = '';

    public array $type = [
        'coding' => [['system' => 'HEALTHCARE_SERVICE_PHARMACY_DRUGS_TYPES']]
    ];

    public string $licenseId;

    public string $comment;

    public const HEALTHCARE_SERVICE_LEGAL_ENTITIES_ALLOWED_TYPE = 'MSP';
    public const LEGAL_ENTITY_PRIMARY_CARE_PROVIDING_CONDITIONS = 'OUTPATIENT';

    public const HEALTHCARE_SERVICE_FORM_CLEANUP = [
        'speciality_type',
        'comment',
        'available_time',
        'not_available'
    ];

    /**
     * Rules based on: https://e-health-ua.atlassian.net/wiki/spaces/EH/pages/17089101853/Create+healthcare+service#Request-data-validation
     *
     * @return array
     */
    public function rules(): array
    {
        $categoriesConfigKey = 'healthcare_service_' . strtolower(legalEntity()->type) . '_categories';
        $providingConditionConfigKey = 'legal_entity_' . strtolower(legalEntity()->type) . '_providing_conditions';

        return [
            'divisionId' => ['required', 'uuid', Rule::exists('divisions', 'uuid')->where('status', Status::ACTIVE)],
            'category' => ['array', 'required'],
            'category.coding.*.system' => ['required', 'string', Rule::in('HEALTHCARE_SERVICE_CATEGORIES')],
            'category.coding.*.code' => [
                'required',
                'string',
                new InDictionary('HEALTHCARE_SERVICE_CATEGORIES'),
                Rule::in(config("ehealth.$categoriesConfigKey", []))
            ],
            'specialityType' => [
                'nullable',
                'string',
                new InDictionary('SPECIALITY_TYPE'),
                'required_if:category.coding.0.code,' . Type::MSP->value
            ],
            'providingCondition' => [
                'nullable',
                'string',
                new InDictionary('PROVIDING_CONDITION'),
                Rule::in(config("ehealth.$providingConditionConfigKey", []))
            ],
            'type' => ['array', 'nullable'],
            'type.coding.*.system' => ['nullable', 'string', Rule::in('HEALTHCARE_SERVICE_PHARMACY_DRUGS_TYPES')],
            'type.coding.*.code' => [
                'nullable',
                'string',
                new InDictionary(['HEALTHCARE_SERVICE_PHARMACY_DRUGS_TYPES', 'LEGAL_ENTITY_TYPE_V2']),
                'required_if:category.coding.0.code,' . Type::PHARMACY_DRUGS->value
            ],
            'licenseId' => [
                'nullable',
                'uuid',
                Rule::exists('licenses', 'uuid')->where('is_active', true)
                    ->where(function (QueryBuilder $query) {
                        $query->where('expiry_date', '>=', now())->orWhereNull('expiry_date');
                    }),
                'required_if:category.coding.0.code,' . Type::PHARMACY->value . ',' . Type::PHARMACY_DRUGS->value
            ],
            'comment' => ['nullable', 'string']
        ];
    }

    /**
     * Redefine field names for error messages.
     *
     * @return array
     */
    protected function validationAttributes(): array
    {
        return [
            'divisionId' => __('forms.division_name')
        ];
    }

    public function getHealthcareService(): array
    {
        return $this->healthcare_service;
    }

    public function healthcareServiceClean(string $category = ''): void
    {
        $formCleanup = self::HEALTHCARE_SERVICE_FORM_CLEANUP;

        if (
            $category === self::HEALTHCARE_SERVICE_LEGAL_ENTITIES_ALLOWED_TYPE &&
            $this->healthcare_service['providing_condition'] === self::LEGAL_ENTITY_PRIMARY_CARE_PROVIDING_CONDITIONS
        ) {
            $this->healthcare_service = array_filter(
                $this->healthcare_service,
                function ($key) use ($formCleanup) {
                    return !in_array($key, $formCleanup);
                },
                ARRAY_FILTER_USE_KEY
            );
        } else {
            $this->healthcare_service = [];
        }
    }

    public function getHealthcareServiceParam(string $param): mixed
    {
        return $this->healthcare_service[$param] ?? '';
    }

    public function setHealthcareServiceParam(string $param, mixed $value): void
    {
        $this->healthcare_service[$param] = $value;
    }

    protected function customRulesValidation(string $mode): string
    {
        foreach ($this->customRules($mode) as $rule) {
            try {
                $rule->validate('', '', fn () => null);
            } catch (CustomValidationException $e) {
                return $e->getMessage();
            }
        }

        return '';
    }

    /**
     * Do form's validation (correctness of filling the form fields)
     *
     * @return array
     * @throws ValidationException
     */
    public function doValidation(): array
    {
        $validated = $this->validate();

        $this->validateConstraint();

        if (empty($validated['type']['coding'][0]['code'])) {
            unset($validated['type']);
        }

        return $validated;
    }

    /**
     * Validate constraint based on: https://e-health-ua.atlassian.net/wiki/spaces/EH/pages/17089101853/Create+healthcare+service#Validate-constraint
     *
     * @return void
     */
    protected function validateConstraint(): void
    {
        $divisionId = Division::whereUuid($this->divisionId)->value('id');
        $categoryCode = data_get($this->category, 'coding.0.code');
        $typeCode = data_get($this->type, 'coding.0.code');

        if (!empty($this->specialityType) && !empty($this->providingCondition)) {
            $firstCheck = HealthcareService::whereDivisionId($divisionId)
                ->whereSpecialityType($this->specialityType)
                ->whereProvidingCondition($this->providingCondition)
                ->exists();

            if ($firstCheck) {
                throw ValidationException::withMessages([
                    'unique_combination' => __('validation.attributes.healthcareService.constraint.typeAndCondition')
                ]);
            }
        }

        if (!empty($categoryCode) && !empty($typeCode)) {
            $secondCheck = HealthcareService::whereDivisionId($divisionId)
                ->whereHas('category.coding', fn (EloquentBuilder $query) => $query->where('code', $categoryCode))
                ->whereHas('type.coding', fn (EloquentBuilder $query) => $query->where('code', $typeCode))
                ->exists();

            if ($secondCheck) {
                throw ValidationException::withMessages([
                    'unique_combination' => __('validation.attributes.healthcareService.constraint.categoryAndType')
                ]);
            }
        }

        $thirdCheck = HealthcareService::whereDivisionId($divisionId)
            ->whereHas('category.coding', fn (EloquentBuilder $query) => $query->where('code', Type::PHARMACY))
            ->exists();

        if ($thirdCheck) {
            throw ValidationException::withMessages([
                'unique_combination' => __('validation.attributes.healthcareService.constraint.categoryPharmacy')
            ]);
        }
    }

    public function addAvailableTime($k): void
    {
        $this->healthcare_service['available_time'][$k] = [
            'days_of_week' => get_day_key($k),
            'all_day' => false,
            'available_start_time' => '',
            'available_end_time' => '',
        ];
    }

    public function removeAvailableTime($k): void
    {
        unset($this->healthcare_service['available_time'][$k]);
    }

    public function addNotAvailableTime(): void
    {
        $this->healthcare_service['not_available'][] = [
            'description' => '',
            'during' => [
                'start' => '',
                'end' => '',
            ],
        ];
    }

    public function removeNotAvailable($k): void
    {
        unset($this->healthcare_service['not_available'][$k]);
    }

    /**
     * TODO: add rule for next cases:
     *  - Check that division exists in PRM DB
     *  - Validate category for HEALTHCARE_SERVICE_<$.category>_LICENSE_TYPE
     *      - check that Healthcare service category must have linked license
     *      - check that License must not be submitted for healthcare service category
     *      - check that License type does not match healthcare service category
     *  - Check that providing condition in request is allowed for legal entity type according to 'Configurations for Healthcare services' ??
     */
    protected function customRules(string $mode)
    {
        $division = $this->component->division;

        $validationRules = [];

        $timeValidationRules = [
            // Check that end time should be greater then start
            new AvailableTimeRule($division, $this->healthcare_service),
            // Check that end time should be greater then start
            new NotAvailableTimeRule($division, $this->healthcare_service)
        ];

        $storeValidationRules = [
            // Check that there is no another record with the same healthcare service, division_id and category = ‘PHARMACY’
            new CategoryInPharmacyRule($division),
            // Check that there is any valid license for the healthcare service's category
            new LicenseRule($division, $this->healthcare_service),
            // Check that there is no another record with the same healthcare service, division_id, category and type
            new CategoryRule($division, $this->healthcare_service),
            // Check that there is no another record with the same healthcare service, division_id, speciality type and providing condition
            new ProvidingConditionRule($division, $this->healthcare_service),
        ];

        if ($mode === 'edit') {
            $validationRules = array_merge($validationRules, $timeValidationRules);
        } else {
            $validationRules = array_merge(
                $validationRules,
                $storeValidationRules,
                $timeValidationRules
            );
        }

        return $validationRules;
    }
}
