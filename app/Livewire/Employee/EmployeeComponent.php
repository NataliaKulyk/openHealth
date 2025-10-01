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
    public array $employeeTypeSpecialities = [];
    public array $employeeTypeLevels = [];
    public array $employeeTypeDegrees = [];
    public array $employeeTypeQualifications = [];
    public array $employeeTypeSpecQualifications = [];

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
            $allowedEmployeeTypes = config('ehealth.legal_entity_employee_types.' . legalEntity()->type, []);

            $this->dictionaries['EMPLOYEE_TYPE'] = array_intersect_key(
                $this->dictionaries['EMPLOYEE_TYPE'] ?? [],
                array_flip($allowedEmployeeTypes)
            );

            foreach ($this->dictionaries['EMPLOYEE_TYPE'] as $employeeType => $description) {

                $allowedQualKeys = config("ehealth.employee_type.{$employeeType}.qualification_type", []);
                $masterQualDict = $this->dictionaries['QUALIFICATION_TYPE'] ?? [];
                $this->employeeTypeQualifications[$employeeType] = array_intersect_key($masterQualDict, array_flip($allowedQualKeys));

                $allowedSpecQualKeys = config("ehealth.employee_type.{$employeeType}.speciality_qualification_type", []);
                $masterSpecQualDict = $this->dictionaries['SPEC_QUALIFICATION_TYPE'] ?? [];
                $this->employeeTypeSpecQualifications[$employeeType] = array_intersect_key($masterSpecQualDict, array_flip($allowedSpecQualKeys));

                $allowedPositionKeys = config("ehealth.employee_type.{$employeeType}.position", []);
                $masterPositionDict = $this->dictionaries['POSITION'] ?? [];
                $this->employeeTypePosition[$employeeType] = array_intersect_key($masterPositionDict, array_flip($allowedPositionKeys));

                $allowedSpecialityKeys = config("ehealth.employee_type.{$employeeType}.speciality_type", []);
                $masterSpecialityDict = $this->dictionaries['SPECIALITY_TYPE'] ?? [];
                $this->employeeTypeSpecialities[$employeeType] = array_intersect_key($masterSpecialityDict, array_flip($allowedSpecialityKeys));

                $allowedLevelKeys = config("ehealth.employee_type.{$employeeType}.speciality_level", []);
                $masterLevelDict = $this->dictionaries['SPECIALITY_LEVEL'] ?? [];
                $this->employeeTypeLevels[$employeeType] = array_intersect_key($masterLevelDict, array_flip($allowedLevelKeys));

                $allowedDegreeKeys = config("ehealth.employee_type.{$employeeType}.education_degree", []);
                $masterDegreeDict = $this->dictionaries['EDUCATION_DEGREE'] ?? [];
                $this->employeeTypeDegrees[$employeeType] = array_intersect_key($masterDegreeDict, array_flip($allowedDegreeKeys));
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
