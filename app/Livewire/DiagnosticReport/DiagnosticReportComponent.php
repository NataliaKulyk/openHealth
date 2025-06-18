<?php

declare(strict_types=1);

namespace App\Livewire\DiagnosticReport;

use App\Classes\Cipher\Exceptions\ApiException as CipherApiException;
use App\Classes\Cipher\Traits\Cipher;
use App\Classes\eHealth\Exceptions\ApiException as eHealthApiException;
use App\Livewire\DiagnosticReport\Forms\DiagnosticReportForm as Form;
use App\Models\Person\Person;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class DiagnosticReportComponent extends Component
{
    use FormTrait;
    use Cipher;
    use WithFileUploads;

    public Form $form;

    /**
     * ID of the patient for which create an encounter.
     * @var int
     */
    #[Locked]
    public int $patientId;

    /**
     * Patient UUID for API requests.
     * @var string
     */
    public string $patientUuid;

    /**
     * Patient first name.
     * @var string
     */
    public string $firstName;

    /**
     * Patient last name.
     * @var string
     */
    public string $lastName;

    /**
     * Patient second name.
     * @var string|null
     */
    public ?string $secondName = null;

    /**
     * List of authorized user's divisions.
     * @var array
     */
    public array $divisions;

    /**
     * Full name of employee.
     * @var string
     */
    public string $employeeFullName;

    /**
     * List of observation codes for categories.
     * @var array
     */
    public array $observationCodeMap;

    /**
     * List of observation values and type of data for specific categories.
     * @var array
     */
    public array $observationValueMap;

    /**
     * List of values for codeable concept.
     * @var array
     */
    public array $codeableConceptValues;

    /**
     * KEP key.
     * @var object|null
     */
    public ?object $file = null;

    /**
     * Found the ICD-10 code and description.
     * @var array
     */
    public array $results;

    protected array $dictionaryNames = [
        'eHealth/diagnostic_report_categories',
        'eHealth/report_origins',
        'eHealth/observation_categories',
        'eHealth/ICF/observation_categories',
        'eHealth/LOINC/observation_codes',
        'eHealth/ICF/classifiers',
        'eHealth/ucum/units',
        'eHealth/ICF/qualifiers/extent_or_magnitude_of_impairment',
        'eHealth/observation_interpretations',
        'eHealth/ICF/qualifiers/nature_of_change_in_body_structure',
        'eHealth/ICF/qualifiers/anatomical_localization',
        'eHealth/ICF/qualifiers/performance',
        'eHealth/ICF/qualifiers/capacity',
        'eHealth/ICF/qualifiers/barrier_or_facilitator',
        'eHealth/observation_methods',
        'eHealth/body_sites',
        'eHealth/stature',
        'eHealth/eye_colour',
        'eHealth/hair_color',
        'eHealth/hair_length',
        'GENDER',
        'eHealth/LOINC/LL360-9',
        'eHealth/LOINC/LL2419-1',
        'eHealth/LOINC/LL4129-4',
        'eHealth/rankin_scale',
        'eHealth/LOINC/LL2009-0',
        'eHealth/LOINC/LL2021-5',
        'eHealth/vaccination_covid_groups',
        'eHealth/LOINC/LL2451-4',
        'eHealth/LOINC/LL3250-9'
    ];

    public function mount(int $patientId): void
    {
        $authUser = Auth::user();

        if (!$authUser) {
            throw new RuntimeException('Authenticated user not found');
        }

        $this->patientId = $patientId;
        $this->employeeFullName = $authUser->getEncounterWriterEmployee()->fullName;

        $this->setPatientData();
        $this->divisions = $authUser->legalEntity->division->toArray();

        $this->getDictionary();

        try {
            $this->dictionaries['custom/services'] = dictionary()->getServiceDictionary();
            $this->loadObservationDictionaries();
        } catch (RuntimeException) {
            Log::channel('e_health_errors')
                ->error('Error while loading observation dictionary in DiagnosticReportComponent');
        }

        try {
            $this->setCertificateAuthority();
        } catch (CipherApiException) {
            $this->flashGeneralError();
        }
    }

    /**
     * Search for ICD-10 in DB by the provided value.
     *
     * @param  string  $value
     * @return void
     */
    public function searchICD10(string $value): void
    {
        $this->results = DB::table('icd_10')
            ->select(['code', 'description'])
            ->where('code', 'ILIKE', "%$value%")
            ->orWhere('description', 'ILIKE', "%$value%")
            ->limit(50)
            ->get()
            ->toArray();
    }

    /**
     * Open modal by provided model name.
     *
     * @param  string  $model
     * @return void
     */
    public function create(string $model): void
    {
        $this->openModal($model);
    }

    public function updatedFile(): void
    {
        $this->keyContainerUpload = $this->file;
    }

    /**
     * Set patient data.
     *
     * @return void
     */
    protected function setPatientData(): void
    {
        $patient = Person::select(['uuid', 'first_name', 'last_name', 'second_name'])
            ->where('id', $this->patientId)
            ->firstOrFail();

        $this->patientUuid = $patient->uuid;
        $this->firstName = $patient->first_name;
        $this->lastName = $patient->last_name;
        $this->secondName = $patient->second_name;
    }

    /**
     * Get Certificate Authority from API.
     *
     * @return array
     * @throws CipherApiException
     */
    protected function setCertificateAuthority(): array
    {
        return $this->getCertificateAuthority = $this->getCertificateAuthority();
    }

    /**
     * Loads dictionaries and related mappings for observations.
     *
     * @return void
     */
    protected function loadObservationDictionaries(): void
    {
        try {
            $this->dictionaries['eHealth/ICF/classifiers'] = dictionary()
                ->getLargeDictionary('eHealth/ICF/classifiers', false)
                ->getFlattenedChildValues();
        } catch (eHealthApiException) {
            $this->flashGeneralError();
        }

        $this->observationCodeMap = config('ehealth.observation_category_codes');
        $this->observationValueMap = config('ehealth.observation_code_values');

        $this->codeableConceptValues = collect(config('ehealth.observation_code_values'))
            ->filter(static fn (array $value) => $value[1] === 'valueCodeableConcept')
            ->mapWithKeys(fn (array $value) => [
                $value[0] => $this->dictionaries[$value[0]] ?? []
            ])
            ->toArray();
    }
}
