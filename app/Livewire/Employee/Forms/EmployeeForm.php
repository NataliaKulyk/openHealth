<?php

namespace App\Livewire\Employee\Forms;

use App\Rules\BirthDate;
use App\Rules\Email;
use App\Rules\Name;
use Illuminate\Support\Facades\Validator;
use Livewire\Form;
use App\Rules\PhoneNumber;
use Illuminate\Validation\ValidationException;
use App\Core\Arr;

/**
 * @property-read array $rules
 */
class EmployeeForm extends Form
{
    public string $position = '';
    public string $employeeType = '';
    public string $startDate = '';
    public ?string $endDate = null;
    public string $status = 'NEW';

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
            [
                'type' => '',
                'number' => '',
            ]
        ],
        'taxId' => '',
        'noTaxId' => false,
        'email' => '',
        'workingExperience' => null,
        'aboutMyself' => null,
    ];

    public array $doctor = [
        'number' => '',
        'issued_date' => '',
        'valid_to' => '',
        'specialities' => [],
        'scienceDegrees' => [],
        'qualifications' => [],
        'educations' => [],
    ];


    /**
     * Defines the validation rules for the form.
     * @return array
     */
    protected function rules(): array
    {
        return array_merge(
            $this->rootFieldsRules(),
            $this->partyRules(),
            $this->documentsRules(),
            $this->doctorRules(),
            $this->kepRules()
        );
    }

    /**
     * Defines validation rules for root-level data.
     * @return array
     */
    protected function rootFieldsRules(): array
    {
        return [
            'position' => ['required', 'string'],
            'employeeType' => ['required', 'string'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date'],
            'status' => ['required', 'string'],
        ];
    }

    /**
     * Defines validation rules for party-related data.
     * @return array
     */
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
            'party.taxId' => [
                'required', 'string',
            ],
            'party.noTaxId' => ['boolean'],
            'party.email' => ['nullable', 'email', new Email()],
            'party.workingExperience' => ['nullable', 'numeric'],
            'party.aboutMyself' => ['nullable', 'string'],
        ];
    }

    /**
     * Defines validation rules for document-related data (now top-level).
     * @return array
     */
    protected function documentsRules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.type' => ['required', 'string'],
            'documents.*.number' => ['required', 'string', 'max:255'],
            'documents.*.issuedAt' => ['required', 'date'],
            'documents.*.issuedBy' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Defines validation rules for doctor-related data (now nested under 'doctor').
     * @return array
     */
    protected function doctorRules(): array
    {
        $doctorTypes = config('ehealth.doctors_type');
        $isDoctor = in_array($this->employeeType, $doctorTypes, true);
        $doctorArrayBaseRules = ['array'];
        $doctorRequiredArrayRules = ['required', 'array', 'min:1'];

        return [
            'doctor.divisionUuid' => ['nullable', 'string', 'uuid'],

            'doctor.educations' => $isDoctor ? $doctorRequiredArrayRules : $doctorArrayBaseRules,
            'doctor.educations.*.country' => ['required', 'string', 'max:255'],
            'doctor.educations.*.city' => ['required', 'string', 'max:255'],
            'doctor.educations.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.educations.*.issuedDate' => ['nullable', 'date'],
            'doctor.educations.*.diplomaNumber' => ['required', 'string', 'max:255'],
            'doctor.educations.*.degree' => ['required', 'string', 'max:255'],
            'doctor.educations.*.speciality' => ['required', 'string', 'max:255'],

            'doctor.specialities' => $isDoctor ? $doctorRequiredArrayRules : $doctorArrayBaseRules,
            'doctor.specialities.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.specialityOfficio' => ['required', 'boolean'],
            'doctor.specialities.*.level' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.qualificationType' => ['required', 'string'],
            'doctor.specialities.*.attestationName' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.attestationDate' => ['required', 'date'],
            'doctor.specialities.*.validToDate' => ['nullable', 'date'],
            'doctor.specialities.*.certificateNumber' => ['required', 'string', 'max:255'],

            'doctor.scienceDegrees' => $isDoctor ? $doctorRequiredArrayRules : $doctorArrayBaseRules,
            'doctor.scienceDegrees.*.country' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.city' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.degree' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.diplomaNumber' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.issuedDate' => ['nullable', 'date'],

            'doctor.qualifications' => ['nullable', 'array'],
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
     * Defines validation rules for KEP (Key Electronic Signature) related fields.
     * These are directly on the form object.
     * @return array
     */
    protected function kepRules(): array
    {
        return [
            'knedp' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'keyContainerUpload' => ['nullable', 'file', 'mimes:p7s,jks,pfx'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function validated(?array $rules = null, array $messages = [], array $attributes = []): array // <-- ЗМІНЕНО ТУТ
    {
        $rules = $rules ?? $this->rules();

        $dataToValidate = Arr::toCamelCase($this->all());

        $validator = Validator::make($dataToValidate, $rules, $messages, $attributes);

        try {
            $validatedData = $validator->validate();
        } catch (ValidationException $e) {
            throw $e;
        }

        return $validatedData;
    }

    public function reset(...$properties): void
    {
        parent::reset(...$properties);
        $this->position = '';
        $this->employeeType = '';
        $this->startDate = '';
        $this->endDate = null;
        $this->status = 'NEW';
        $this->documents = [];
        $this->party = [
            'lastName' => '',
            'firstName' => '',
            'secondName' => '',
            'gender' => '',
            'birthDate' => '',
            'phones' => [
                [
                    'type' => '',
                    'number' => '',
                ]
            ],
            'taxId' => '',
            'noTaxId' => false,
            'email' => '',
            'workingExperience' => null,
            'aboutMyself' => null,
        ];
        $this->doctor = [
            'divisionUuid' => null,
            'educations' => [],
            'specialities' => [],
            'scienceDegrees' => [],
            'qualifications' => [],
        ];
        $this->knedp = null;
        $this->keyContainerUpload = null;
        $this->password = null;
    }

    public function validateBeforeSendApi(): array
    {
        $doctorTypes = config('ehealth.doctors_type');

        if (empty($this->documents)) {
            return [
                'error' => true,
                'message' => __('validation.custom.documentsEmpty'),
            ];
        }

        if (in_array($this->employeeType, $doctorTypes, true)) {
            if (empty($this->doctor['specialities'])) {
                return [
                    'error' => true,
                    'message' => __('validation.custom.specialityTable'),
                ];
            }

            if (empty($this->doctor['educations'])) {
                return [
                    'error' => true,
                    'message' => __('validation.custom.educationTable'),
                ];
            }

            if (empty($this->doctor['scienceDegrees'])) {
                return [
                    'error' => true,
                    'message' => __('validation.custom.science_degreesTable'),
                ];
            }
        }

        return [
            'error' => false,
            'message' => '',
        ];
    }

    /**
     * Prepares and returns all form data with keys converted to snake_case
     * and structured for direct use by the repository.
     *
     * @return array
     */
    public function getPreparedData(): array
    {
        $formData = $this->all();

        $preparedData = [
            'position' => $formData['position'],
            'employee_type' => $formData['employeeType'],
            'start_date' => $formData['startDate'],
            'status' => $formData['status'],
        ];

        // Додаємо end_date окремо, якщо воно не null
        if (!empty($formData['endDate'])) {
            $preparedData['end_date'] = $formData['endDate'];
        } else {
            $preparedData['end_date'] = null;
        }


        $preparedParty = Arr::toSnakeCase($formData['party']);
        $preparedParty['phones'] = collect($preparedParty['phones'] ?? [])
            ->map(fn($phone) => Arr::toSnakeCase($phone))
            ->toArray();
        $preparedData['party'] = $preparedParty;

        $preparedDoctor = Arr::toSnakeCase($formData['doctor']);

        $preparedDoctor['specialities'] = collect($preparedDoctor['specialities'] ?? [])
            ->map(fn($s) => Arr::toSnakeCase($s))
            ->toArray();
        $preparedDoctor['science_degrees'] = collect($preparedDoctor['science_degrees'] ?? [])
            ->map(fn($s) => Arr::toSnakeCase($s))
            ->toArray();
        $preparedDoctor['qualifications'] = collect($preparedDoctor['qualifications'] ?? [])
            ->map(fn($q) => Arr::toSnakeCase($q))
            ->toArray();
        $preparedDoctor['educations'] = collect($preparedDoctor['educations'] ?? [])
            ->map(fn($e) => Arr::toSnakeCase($e))
            ->toArray();
        $preparedData['doctor'] = $preparedDoctor;

        $preparedData['documents'] = collect($formData['documents'] ?? [])
            ->map(fn($doc) => Arr::toSnakeCase($doc))
            ->toArray();

        return $preparedData;
    }
}
