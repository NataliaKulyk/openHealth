<?php

namespace App\Livewire\Employee\Forms;

use App\Models\Employee\Employee;
use App\Models\Relations\Party;
use App\Rules\BirthDate;
use App\Rules\Email;
use App\Rules\Name;
use App\Rules\UniqueEmailInLegalEntity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Form;
use App\Rules\PhoneNumber;
use App\Core\Arr;
use Exception;
use Carbon\Carbon;

class EmployeeForm extends Form
{
    public string $position = '';
    public string $employeeType = '';
    public string $startDate = '';
    public ?string $endDate = null;
    public string $status = 'NEW';
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
        'phones' => [
            ['type' => '', 'number' => ''],
        ],
        'taxId' => '',
        'noTaxId' => false,
        'email' => '',
        'workingExperience' => null,
        'aboutMyself' => '',
    ];

    public array $doctor = [
        'divisionUuid' => null,
        'specialities' => [],
        'scienceDegrees' => [],
        'qualifications' => [],
        'educations' => [],
    ];

    /**
     * Defines all validation rules for the form.
     *
     * @return array
     */
    public function rulesForSave(): array
    {
        return array_merge(
            $this->rootFieldsRules(),
            $this->partyRules(),
            $this->documentsRules(),
            $this->doctorRules()
        );
    }

    /**
     * Defines validation rules for the signature block only.
     *
     * @return array
     */
    public function rulesForKepOnly(): array
    {
        return [
            'knedp'              => ['required', 'string'],
            'password'           => ['required', 'string'],
            'keyContainerUpload' => ['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'],
        ];
    }

    /**
     * Defines validation rules for root-level data.
     *
     * @return array
     */
    protected function rootFieldsRules(): array
    {
        return [
            'position'     => ['required', 'string'],
            'employeeType' => ['required', 'string'],
            'startDate'    => ['required', 'date'],
            'endDate'      => ['nullable', 'date'],
            'status'       => ['required', 'string'],
        ];
    }

    /**
     * Defines validation rules for party-related data.
     *
     * @return array
     */
    protected function partyRules(): array
    {
        $partyIdToIgnore = $this->existingPartyId;

        return [
            'party.lastName'          => ['required', new Name()],
            'party.firstName'         => ['required', new Name()],
            'party.secondName'        => ['nullable', new Name()],
            'party.gender'            => ['required', 'string'],
            'party.birthDate'         => ['required', 'date', new BirthDate()],
            'party.phones'            => ['required', 'array', 'min:1'],
            'party.phones.*.number'   => ['required', new PhoneNumber()],
            'party.phones.*.type'     => ['required', 'string'],
            'party.taxId'             => ['required', 'string'],
            'party.noTaxId'           => ['boolean'],
            'party.email'             => [
                'nullable',
                new Email(),
                new UniqueEmailInLegalEntity($partyIdToIgnore)
            ],
            'party.workingExperience' => ['required', 'numeric', 'min:1'],
            'party.aboutMyself'       => ['nullable', 'string'],
        ];
    }

    /**
     * Defines validation rules for document-related data (now top-level).
     * Changed to nullable based on API documentation and repository validation.
     *
     * @return array
     */
    protected function documentsRules(): array
    {
        return [
            'documents'            => ['required', 'array', 'min:1'],
            'documents.*.type'     => ['required', 'string'],
            'documents.*.number'   => ['required', 'string'],
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
        $isDoctor    = in_array($this->employeeType, $doctorTypes, true);

        $educationRules    = ['nullable', 'array'];
        $specialitiesRules = ['nullable', 'array'];

        if ($isDoctor) {
            $educationRules[]    = 'required';
            $educationRules[]    = 'min:1';
            $specialitiesRules[] = 'required';
            $specialitiesRules[] = 'min:1';
        }

        $scienceDegreesRules = ['nullable', 'array'];
        $qualificationsRules = ['nullable', 'array'];

        return [
            'doctor.divisionUuid'                 => ['nullable', 'string', 'uuid'],
            'doctor.educations'                   => $educationRules,
            'doctor.educations.*.country'         => ['required', 'string', 'max:255'],
            'doctor.educations.*.city'            => ['required', 'string', 'max:255'],
            'doctor.educations.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.educations.*.issuedDate'      => ['nullable', 'date'],
            'doctor.educations.*.diplomaNumber'   => ['required', 'string', 'max:255'],
            'doctor.educations.*.degree'          => ['required', 'string', 'max:255'],
            'doctor.educations.*.speciality'      => ['required', 'string', 'max:255'],

            'doctor.specialities'                     => $specialitiesRules,
            'doctor.specialities.*.speciality'        => ['required', 'string', 'max:255'],
            'doctor.specialities.*.specialityOfficio' => ['required', 'boolean'],
            'doctor.specialities.*.level'             => ['required', 'string', 'max:255'],
            'doctor.specialities.*.qualificationType' => ['required', 'string'],
            'doctor.specialities.*.attestationName'   => ['required', 'string', 'max:255'],
            'doctor.specialities.*.attestationDate'   => ['required', 'date'],
            'doctor.specialities.*.validToDate'       => ['nullable', 'date'],
            'doctor.specialities.*.certificateNumber' => ['required', 'string', 'max:255'],

            'doctor.scienceDegrees'                   => $scienceDegreesRules,
            'doctor.scienceDegrees.*.country'         => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.city'            => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.degree'          => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.diplomaNumber'   => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.speciality'      => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.issuedDate'      => ['nullable', 'date'],

            'doctor.qualifications'                     => $qualificationsRules,
            'doctor.qualifications.*.type'              => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.institutionName'   => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.speciality'        => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.issuedDate'        => ['required', 'date'],
            'doctor.qualifications.*.certificateNumber' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.validTo'           => ['nullable', 'date', 'after_or_equal:doctor.qualifications.*.issuedDate'],
            'doctor.qualifications.*.additionalInfo'    => ['nullable', 'string'],
        ];
    }

    /**
     * Defines validation rules for KEP (Key Electronic Signature) related fields.
     *
     * @param bool $required Вказує, чи є поля обов'язковими.
     *
     * @return array
     */
    protected function kepRules(bool $required = false): array
    {
        $rules = $required ? ['required'] : ['nullable'];

        return [
            'knedp'              => [...$rules, 'string'],
            'password'           => [...$rules, 'string'],
            'keyContainerUpload' => [...$rules, 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'],
        ];
    }

    /**
     * Processes the uploaded KEP file and returns its base64 content.
     *
     * @return string|null Base64 encoded file content, or null if an error occurred.
     * @throws Exception If file content cannot be read.
     */
    //TODO в SignService обробку експепшена
    public function getBase64KepFileContent(): ?string
    {
        if ($this->keyContainerUpload && $this->keyContainerUpload->exists()) {
            $fileExtension = $this->keyContainerUpload->getClientOriginalExtension();
            $fileName      = 'kep_' . uniqid('', true) . '.' . $fileExtension;
            $filePath      = $this->keyContainerUpload->storeAs('uploads/kep', $fileName, 'public');

            if ($filePath) {
                $fileContents = file_get_contents(Storage::path('public/' . $filePath));
                Storage::disk('public')->delete($filePath);

                if ($fileContents !== false) {
                    return base64_encode($fileContents);
                } else {
                    throw new \RuntimeException(__('Не вдалося прочитати вміст файлу КЕП.'));
                }
            } else {
                throw new \RuntimeException(__('Не вдалося зберегти завантажений файл КЕП.'));
            }
        }
        throw new \RuntimeException(__('Будь ласка, завантажте файл КЕП.'));
    }

    /**
     * Populates the form fields from a full Employee model based on the view mode.
     */
    public function populateFromModel(Employee $employee, string $viewMode = 'full_edit'): void
    {
        $employee->loadMissing(['party.phones', 'party.documents']);

        // This part always runs: we always need the personal data.
        if ($employee->party) {
            $this->populateFromParty($employee->party);
        }

        // Populate positional and doctor data ONLY if we are NOT in 'add_position' mode.
        if ($viewMode !== 'add_position') {
            $employee->loadMissing(['educations', 'specialities', 'qualifications', 'scienceDegrees']);

            $this->position = $employee->position;
            $this->employeeType = $employee->employee_type;
            $this->startDate = $employee->start_date?->format('Y-m-d');
            $this->endDate = $employee->end_date?->format('Y-m-d');
            $this->divisionId = $employee->division_id;

            // Populate doctor-specific arrays if needed
            $this->doctor['educations'] = $employee->educations->map(fn($edu) => Arr::toCamelCase($edu->toArray()))->toArray();
            $this->doctor['specialities'] = $employee->specialities->map(fn($spec) => Arr::toCamelCase($spec->toArray()))->toArray();
            $this->doctor['qualifications'] = $employee->qualifications->map(fn($qual) => Arr::toCamelCase($qual->toArray()))->toArray();
            $this->doctor['scienceDegrees'] = $employee->scienceDegrees->map(fn($degree) => Arr::toCamelCase($degree->toArray()))->toArray();
        }
        // If the mode is 'add_position', these fields will simply keep their default empty values.
        // The resetPositionFields() method is no longer needed.
    }

    /**
     * Populates ONLY party and document fields from an existing Party model.
     */
    public function populateFromParty(Party $party): void
    {
        $party->loadMissing(['phones', 'documents']);
        $this->existingPartyId = $party->id;

        $this->party = [
            'lastName' => $party->last_name, 'firstName' => $party->first_name,
            'secondName' => $party->second_name, 'gender' => $party->gender,
            'birthDate' => $party->birth_date?->format('Y-m-d'), 'taxId' => $party->tax_id,
            'noTaxId' => (bool)$party->no_tax_id, 'email' => $party->email,
            'workingExperience' => $party->working_experience, 'aboutMyself' => $party->about_myself,
            'phones' => $party->phones->map(fn($p) => ['type' => $p->type, 'number' => $p->number])->toArray(),
        ];

        $this->documents = $party->documents->map(fn($d) => ['type' => $d->type, 'number' => $d->number, 'issued_by' => $d->issued_by, 'issued_at' => $d->issued_at?->format('Y-m-d')])->toArray();
    }

    /**
     * Recursively formats date strings within an array to 'YYYY-MM-DD'.
     *
     * @param array $data
     *
     * @return array
     */
    protected function formatDatesInArray(array $data): array
    {
        $dateKeys = [
            'startDate', 'endDate', 'birthDate', 'issuedAt', 'issuedDate',
            'attestationDate', 'validToDate', 'validTo'
        ];

        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $value = $this->formatDatesInArray($value);
            } else if (is_string($value) && in_array($key, $dateKeys)) {
                if (!empty($value)) {
                    try {
                        $value = Carbon::parse($value)->format('Y-m-d');
                    } catch (\Exception $e) {
                        Log::warning("Failed to parse date for key '$key': " . $value . ' - ' . $e->getMessage());
                    }
                } else {
                    $value = null;
                }
            }
        }
        return $data;
    }

    /**
     * Prepares and returns all form data, recursively converting keys to snake_case.
     */
    public function getPreparedData(): array
    {
        $formData = $this->all();
        unset($formData['existingPartyId']);

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
            'divisionUuid' => null, 'educations' => [], 'specialities' => [],
            'scienceDegrees' => [], 'qualifications' => [],
        ];
    }
}
