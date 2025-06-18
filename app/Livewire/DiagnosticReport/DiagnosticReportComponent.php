<?php

declare(strict_types=1);

namespace App\Livewire\DiagnosticReport;

use App\Classes\Cipher\Exceptions\ApiException as CipherApiException;
use App\Classes\Cipher\Traits\Cipher;
use App\Livewire\DiagnosticReport\Forms\DiagnosticReportForm as Form;
use App\Models\Person\Person;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        'eHealth/report_origins'
    ];

    public function mount(int $patientId): void
    {
        $authUser = Auth::user();

        if (!$authUser) {
            throw new RuntimeException('Authenticated user not found');
        }

        $this->patientId = $patientId;
        $this->employeeFullName = $authUser->getEncounterWriterEmployee()->fullName;

        $this->getDictionary();
        $this->dictionaries['custom/services'] = dictionary()->getServiceDictionary();

        $this->setPatientData();
        $this->divisions = $authUser->legalEntity->division->toArray();

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
}
