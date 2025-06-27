<?php

namespace App\Livewire\Employee\Forms;

use App\Core\Arr;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Relations\Party;
use App\Rules\BirthDate;
use App\Rules\Name;
use App\Rules\PhoneNumber;
use App\Rules\UniqueEmailInLegalEntity;
use Livewire\Form;

class EmployeeForm extends Form
{
    public string $position = '';
    public string $employeeType = '';
    public string $startDate = '';
    public ?string $endDate = null;
    public ?int $existingPartyId = null;
    public ?string $divisionId = null;

    public ?string $knedp = null;
    public $keyContainerUpload;
    public ?string $password = null;

    public array $documents = [];
    public array $party = [
        'lastName' => '',
        'firstName' => '',
        'secondName' => '',
        'gender' => '',
        'birthDate' => '',
        'phones' => [['type' => '', 'number' => '']],
        'taxId' => '',
        'noTaxId' => false,
        'email' => '',
        'workingExperience' => null,
        'aboutMyself' => '',
    ];
    public array $doctor = [
        'specialities' => [],
        'scienceDegrees' => [],
        'qualifications' => [],
        'educations' => [],
    ];

    public function rulesForSave(): array
    {
        return array_merge(
            $this->rootFieldsRules(),
            $this->partyRules(),
            $this->documentsRules(),
            $this->doctorRules()
        );
    }

    public function rulesForKepOnly(): array
    {
        return [
            'knedp' => ['required', 'string'],
            'password' => ['required', 'string'],
            'keyContainerUpload' => ['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'],
        ];
    }

    protected function rootFieldsRules(): array
    {
        return [
            'position' => ['required', 'string'],
            'employeeType' => ['required', 'string'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date'],
            'divisionId' => ['nullable', 'string'],
        ];
    }

    protected function partyRules(): array
    {
        return [
            'party.lastName' => ['required', new Name()],
            'party.firstName' => ['required', new Name()],
            'party.secondName' => ['nullable', new Name()],
            'party.gender' => ['required', 'string'],
            'party.birthDate' => ['required', 'date', new BirthDate()],
            'party.phones' => ['required', 'array', 'min:1'],
            'party.phones.*.number' => ['required', new PhoneNumber()],
            'party.phones.*.type' => ['required', 'string'],
            'party.taxId' => ['required_if:party.noTaxId,false', 'string'],
            'party.noTaxId' => ['boolean'],
            'party.email' => ['nullable', 'email', new UniqueEmailInLegalEntity($this->existingPartyId)],
            'party.workingExperience' => ['required', 'numeric', 'min:1'],
            'party.aboutMyself' => ['nullable', 'string'],
        ];
    }

    protected function documentsRules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.type' => ['required', 'string'],
            'documents.*.number' => ['required', 'string'],
            'documents.*.issuedBy' => ['required', 'string', 'min:1'],
            'documents.*.issuedAt' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Defines validation rules for doctor-related data (now nested under 'doctor').
     * Updated scienceDegrees and qualifications to be nullable.
     *
     * @return array
     */
    protected function doctorRules(): array
    {
        $doctorTypes = config('ehealth.doctors_type');
        $isDoctor = in_array($this->employeeType, $doctorTypes, true);

        $educationRules = ['nullable', 'array'];
        $specialitiesRules = ['nullable', 'array'];

        if ($isDoctor) {
            $educationRules[] = 'required';
            $educationRules[] = 'min:1';
            $specialitiesRules[] = 'required';
            $specialitiesRules[] = 'min:1';
        }

        $scienceDegreesRules = ['nullable', 'array'];
        $qualificationsRules = ['nullable', 'array'];

        return [
            'doctor.educations' => $educationRules,
            'doctor.educations.*.country' => ['required', 'string', 'max:255'],
            'doctor.educations.*.city' => ['required', 'string', 'max:255'],
            'doctor.educations.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.educations.*.issuedDate' => ['nullable', 'date'],
            'doctor.educations.*.diplomaNumber' => ['required', 'string', 'max:255'],
            'doctor.educations.*.degree' => ['required', 'string', 'max:255'],
            'doctor.educations.*.speciality' => ['required', 'string', 'max:255'],

            'doctor.specialities' => $specialitiesRules,
            'doctor.specialities.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.specialityOfficio' => ['required', 'boolean'],
            'doctor.specialities.*.level' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.qualificationType' => ['required', 'string'],
            'doctor.specialities.*.attestationName' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.attestationDate' => ['required', 'date'],
            'doctor.specialities.*.validToDate' => ['nullable', 'date'],
            'doctor.specialities.*.certificateNumber' => ['required', 'string', 'max:255'],

            'doctor.scienceDegrees' => $scienceDegreesRules,
            'doctor.scienceDegrees.*.country' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.city' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.degree' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.diplomaNumber' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.issuedDate' => ['nullable', 'date'],

            'doctor.qualifications' => $qualificationsRules,
            'doctor.qualifications.*.type' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.issuedDate' => ['required', 'date'],
            'doctor.qualifications.*.certificateNumber' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.validTo' => ['nullable', 'date', 'after_or_equal:doctor.qualifications.*.issuedDate'],
            'doctor.qualifications.*.additionalInfo' => ['nullable', 'string'],
        ];
    }

    /**
     * FIX: The method signature is corrected to accept an Employee model,
     * which resolves the type-hinting error.
     */
    public function populateFromModel(Employee $employee): void
    {
        $employee->loadMissing(['party.phones', 'party.documents', 'educations', 'specialities', 'qualifications', 'scienceDegrees']);

        if ($employee->party) {
            // It correctly calls the helper method with the Party relation.
            $this->populateFromParty($employee->party);
        }

        // It now also populates the employee-specific fields.
        $this->position = $employee->position;
        $this->employeeType = $employee->employee_type;
        $this->startDate = $employee->start_date?->format('Y-m-d');
        $this->endDate = $employee->end_date?->format('Y-m-d');
        $this->divisionId = $employee->division_id;

        $this->doctor['educations'] = $employee->educations->map(fn($edu) => Arr::toCamelCase($edu->toArray()))->toArray();
        $this->doctor['specialities'] = $employee->specialities->map(fn($spec) => Arr::toCamelCase($spec->toArray()))->toArray();
        $this->doctor['qualifications'] = $employee->qualifications->map(fn($qual) => Arr::toCamelCase($qual->toArray()))->toArray();
        $this->doctor['scienceDegrees'] = $employee->scienceDegrees->map(fn($degree) => Arr::toCamelCase($degree->toArray()))->toArray();
    }

    /**
     * Resets only the fields related to a specific position/employment.
     * This is called in the 'Add Position' component.
     */
    public function resetPositionFields(): void
    {
        $this->position = '';
        $this->employeeType = '';
        $this->startDate = '';
        $this->endDate = null;
        $this->divisionId = null;
    }

    /**
     * Populates form fields from an EmployeeRequest's revision data.
     * This method now robustly handles the consistently nested data structure.
     */
    public function populateFromRequest(EmployeeRequest $request): void
    {
        $revisionData = $request->revision->data ?? null;

        if ($revisionData) {
            // Data is always expected in a nested structure now.
            $employeeData = $revisionData['employee_request_data'] ?? [];
            $partyData = $revisionData['party'] ?? [];
            $documentsData = $revisionData['documents'] ?? [];
            $phonesData = $revisionData['phones'] ?? [];
            $doctorData = $revisionData['doctor'] ?? [];

            // Populate main employee fields
            $this->position = $employeeData['position'] ?? '';
            $this->employeeType = $employeeData['employee_type'] ?? '';
            $this->startDate = $employeeData['start_date'] ?? '';
            $this->endDate = $employeeData['end_date'] ?? null;
            $this->divisionId = $employeeData['division_id'] ?? null;

            // Populate party fields
            $this->party['lastName'] = $partyData['last_name'] ?? '';
            $this->party['firstName'] = $partyData['first_name'] ?? '';
            $this->party['secondName'] = $partyData['second_name'] ?? '';
            $this->party['gender'] = $partyData['gender'] ?? '';
            $this->party['birthDate'] = $partyData['birth_date'] ?? '';
            $this->party['taxId'] = $partyData['tax_id'] ?? '';
            $this->party['noTaxId'] = (bool)($partyData['no_tax_id'] ?? false);
            $this->party['email'] = $partyData['email'] ?? '';
            $this->party['workingExperience'] = $partyData['working_experience'] ?? null;
            $this->party['aboutMyself'] = $partyData['about_myself'] ?? '';
            $this->party['phones'] = Arr::toCamelCase($phonesData);

            // Populate documents and doctor data
            $this->documents = Arr::toCamelCase($documentsData);
            $this->doctor = Arr::toCamelCase($doctorData);
        }

        if ($request->party_id) {
            $this->existingPartyId = $request->party_id;
        }
    }

    /**
     * FIX: Renamed back to `populateFromParty` as requested.
     * This method is a helper for populating ONLY personal data from a Party model.
     */
    public function populateFromParty(Party $party): void
    {
        $party->loadMissing(['phones', 'documents']);
        $this->existingPartyId = $party->id;

        $this->party['lastName'] = $party->last_name;
        $this->party['firstName'] = $party->first_name;
        $this->party['secondName'] = $party->second_name;
        $this->party['gender'] = $party->gender;
        $this->party['birthDate'] = $party->birth_date?->format('Y-m-d');
        $this->party['taxId'] = $party->tax_id;
        $this->party['noTaxId'] = (bool)$party->no_tax_id;
        $this->party['email'] = $party->email;
        $this->party['workingExperience'] = $party->working_experience;
        $this->party['aboutMyself'] = $party->about_myself;

        $this->party['phones'] = $party->phones->map(fn($p) => Arr::toCamelCase($p->only(['type', 'number'])))->toArray();
        $this->documents = $party->documents->map(fn($d) => Arr::toCamelCase($d->only(['type', 'number', 'issued_by', 'issued_at'])))->toArray();
    }

    /**
     * Prepares and returns a FLAT array of all form data for the repository.
     * The logic for creating a nested structure for the revision is moved to the Trait.
     */
    public function getPreparedData(): array
    {
        $formData = $this->all();
        $partyData = $formData['party'] ?? [];
        unset($formData['party']);
        $formData = array_merge($formData, $partyData);

        unset(
            $formData['existingPartyId'],
            $formData['knedp'],
            $formData['keyContainerUpload'],
            $formData['password']
        );

        return Arr::toSnakeCase($formData);
    }

    /**
     * Resets the form to its default state.
     */
    public function reset(...$properties): void
    {
        parent::reset(...$properties);

        $this->position = '';
        $this->employeeType = '';
        $this->startDate = '';
        $this->endDate = null;
        $this->existingPartyId = null;

        $this->knedp = null;
        $this->keyContainerUpload = null;
        $this->password = null;

        $this->documents = [];
        $this->party = [
            'lastName' => '', 'firstName' => '', 'secondName' => '', 'gender' => '',
            'birthDate' => '', 'phones' => [['type' => '', 'number' => '']],
            'taxId' => '', 'noTaxId' => false, 'email' => '',
            'workingExperience' => null, 'aboutMyself' => '',
        ];
        $this->doctor    = [
            'scienceDegrees' => [], 'qualifications' => [],
        ];
    }
}
