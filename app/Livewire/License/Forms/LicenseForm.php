<?php

declare(strict_types=1);

namespace App\Livewire\License\Forms;

use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Form;

class LicenseForm extends Form
{
    #[Locked]
    public bool $isPrimary = false;

    public string $type = '';

    public string $orderNo = '';

    public string $issuedBy = '';

    public string $issuedDate = '';

    public string $whatLicensed = '';

    public string $licenseNumber = '';

    public string $activeFromDate = '';

    public string $expiryDate = '';

    /**
     * Set validation rules for the form.
     */
    protected function rules(): array
    {
        $allowedTypes = array_keys($this->getAllowedLicenseTypes());

        return [
            'type' => [
                'required',
                Rule::in($allowedTypes),
                // Check that legal entity does not have license with type same as in request.
                Rule::unique('licenses', 'type')
                    ->where('legal_entity_id', legalEntity()->id)
                    ->ignore($this->component->uuid, 'uuid')
            ],
            'licenseNumber' => ['nullable', 'string'],
            'issuedBy' => ['required', 'string'],
            'issuedDate' => ['required', 'date', 'before_or_equal:activeFromDate'],
            'expiryDate' => ['required_if:type,PHARMACY_DRUGS', 'date', 'after_or_equal:today'],
            'activeFromDate' => ['required', 'date', 'before_or_equal:expiryDate'],
            'whatLicensed' => ['required', 'string'],
            'orderNo' => ['required', 'string'],
            'isPrimary' => ['required', Rule::in([false])]
        ];
    }

    /**
     * Get allowed types based on LEGAL_ENTITY_<LEGAL_ENTITY_TYPE>_ADDITIONAL_LICENSE_TYPES.
     * https://e-health-ua.atlassian.net/wiki/spaces/EH/pages/17092870145/Legal+Entities+configurable+parameters#Configurable-parameters
     *
     * @return array
     */
    private function getAllowedLicenseTypes(): array
    {
        $licenseTypes = dictionary()->getDictionary('LICENSE_TYPE');

        if (legalEntity()->type === 'OUTPATIENT' || legalEntity()->type === 'PHARMACY') {
            return ['PHARMACY_DRUGS' => $licenseTypes['PHARMACY_DRUGS']];
        }

        return $licenseTypes;
    }
}
