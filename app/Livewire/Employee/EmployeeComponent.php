<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use App\Livewire\Employee\Forms\EmployeeForm as Form;

abstract class EmployeeComponent extends Component
{
    use FormTrait {
        getDictionary as traitGetDictionary;
    }

    public Form $form;
    public bool $isPersonalDataLocked = false;
    #[Locked]
    public ?int $employeeRequestId = null;
    public array $divisions = [];
    public bool $showSignatureModal = false;
    public ?array $dictionaryNames = [
        'PHONE_TYPE', 'COUNTRY', 'SETTLEMENT_TYPE', 'SPECIALITY_TYPE', 'DIVISION_TYPE',
        'SPECIALITY_LEVEL', 'GENDER', 'QUALIFICATION_TYPE', 'SCIENCE_DEGREE', 'DOCUMENT_TYPE',
        'SPEC_QUALIFICATION_TYPE', 'EMPLOYEE_TYPE', 'POSITION', 'EDUCATION_DEGREE', 'DIVISION'
    ];
    public ?array $dictionaries = [];
    public array $employeeTypePosition = [];

    /**
     * This is the single, public method that child components will call.
     */
    public function loadDictionaries(): void
    {
        $this->getDictionary();
    }

    /**
     * The protected getDictionary method contains the implementation.
     */
    protected function getDictionary(): void
    {
        $this->traitGetDictionary();

        if (legalEntity()) {
            $this->dictionaries['EMPLOYEE_TYPE'] = $this->getDictionariesFields(
                config('ehealth.legal_entity_type.' . legalEntity()->type . '.roles'),
                'EMPLOYEE_TYPE'
            );
            foreach ($this->dictionaries['EMPLOYEE_TYPE'] as $employeeType => $description) {
                $keys = config("ehealth.employee_type.{$employeeType}.position", []);
                $this->employeeTypePosition[$employeeType] = $this->getDictionariesFields($keys, 'POSITION');
            }
        }
    }

    #[Computed]
    public function employeeFullName(): string
    {
        if (isset($this->employee) && $this->employee->party) {
            return $this->employee->party->fullName;
        }

        if (isset($this->party)) {
            return $this->party->fullName;
        }

        if (!empty($this->form->party['lastName'])) {
            return trim($this->form->party['lastName'] . ' ' . $this->form->party['firstName']);
        }

        return '';
    }

    protected function loadDivisions(LegalEntity $legalEntity): void
    {
        $this->divisions = $legalEntity->divisions()->where('is_active', true)->get(['id', 'name'])->toArray();
    }
}
