<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Traits\FormTrait;
use Livewire\Component;
use App\Livewire\Employee\Forms\EmployeeForm as Form;

class EmployeeComponent extends Component
{
    use FormTrait {
        getDictionary as traitGetDictionary;
    }

    public Form $form;

    /**
     * @var array|string[] Set dictionaries to load with the component
     */
    public ?array $dictionaryNames = [
        'PHONE_TYPE', 'COUNTRY', 'SETTLEMENT_TYPE', 'SPECIALITY_TYPE', 'DIVISION_TYPE',
        'SPECIALITY_LEVEL', 'GENDER', 'QUALIFICATION_TYPE', 'SCIENCE_DEGREE', 'DOCUMENT_TYPE',
        'SPEC_QUALIFICATION_TYPE', 'EMPLOYEE_TYPE', 'POSITION', 'EDUCATION_DEGREE',
    ];

    /**
     * @var array Holds information about relation between employee type and position
     */
    public array $employeeTypePosition = [];

    /**
     * Override FormTrait method to filter dictionary data to specific entity type.
     */
    protected function getDictionary(): void
    {
        $this->traitGetDictionary();

        $this->dictionaries['EMPLOYEE_TYPE'] = $this->getDictionariesFields(
            config('ehealth.legal_entity_type.' . legalEntity()->type .'.roles'),
            'EMPLOYEE_TYPE'
        );

        foreach ($this->dictionaries['EMPLOYEE_TYPE'] as $employeeType => $description) {
            $keys = config("ehealth.employee_type.{$employeeType}.position", []);
            $this->employeeTypePosition[$employeeType] = $this->getDictionariesFields($keys, 'POSITION');
        }
    }
}
