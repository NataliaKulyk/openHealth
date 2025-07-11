<?php

declare(strict_types=1);

namespace App\Livewire\Procedure;

use App\Classes\Cipher\Exceptions\ApiException as CipherApiException;
use App\Classes\Cipher\Traits\Cipher;
use App\Classes\eHealth\Api\PatientApi;
use App\Classes\eHealth\Exceptions\ApiException as eHealthApiException;
use App\Livewire\Procedure\Forms\ProcedureForm as Form;
use App\Livewire\Encounter\Forms\Api\EncounterRequestApi;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class ProcedureComponent extends Component
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
     * List of existing patient episodes.
     * @var array
     */
    public array $episodes = [];

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
     * List of founded procedure reasons.
     * @var array
     */
    public array $procedureReasons = [];

    protected array $dictionaryNames = [
        'eHealth/procedure_categories',
        'eHealth/procedure_outcomes',
        'eHealth/report_origins',
        'eHealth/LOINC/observation_codes',
        'eHealth/ICF/classifiers',
        'eHealth/ICPC2/condition_codes',
        'eHealth/assistive_products'
    ];

    public function mount(LegalEntity $legalEntity, int $patientId): void
    {
        $authUser = Auth::user();

        if (!$authUser) {
            throw new RuntimeException('Authenticated user not found');
        }

        $this->patientId = $patientId;
        $this->employeeFullName = $authUser->getEncounterWriterEmployee()->fullName;

        $this->setPatientData();
        $this->divisions = legalEntity()->division->toArray();
        $this->getDictionary();
        $this->getEpisodes();

        try {
            $this->dictionaries['custom/services'] = dictionary()->getServiceDictionary();
            $this->dictionaries['eHealth/assistive_products'] = dictionary()
                ->getLargeDictionary('eHealth/assistive_products', false)
                ->getFlattenedChildValues();
        } catch (eHealthApiException) {
            Log::channel('e_health_errors')
                ->error('Error while loading services and assistive products dictionaries in ProcedureComponent');
        }

        try {
            $this->setCertificateAuthority();
        } catch (CipherApiException) {
            $this->flashGeneralError();
        }
    }

    /**
     * Search for procedure reasons in conditions and observations.
     *
     * @param  string  $episodeId
     * @return void
     */
    public function searchReasons(string $episodeId): void
    {
        // Validate that an episode ID is provided
        if (empty($episodeId)) {
            $this->addError('episode', 'Please select an episode first.');

            return;
        }

        $buildGetConditions = EncounterRequestApi::buildGetConditionsInEpisodeContext($this->patientUuid, $episodeId);
        $buildGetObservations = EncounterRequestApi::buildGetObservationsInEpisodeContext(
            $this->patientUuid,
            $episodeId
        );

        try {
            $conditions = PatientApi::getConditionsInEpisodeContext(
                $this->patientUuid,
                $episodeId,
                $buildGetConditions
            );
            $observations = PatientApi::getObservationsInEpisodeContext(
                $this->patientUuid,
                $episodeId,
                $buildGetObservations
            );

            $this->procedureReasons = array_merge($conditions, $observations);
        } catch (eHealthApiException) {
            Log::channel('e_health_errors')
                ->error('Error while searching for procedure reasons in Procedure Component');

            $this->flashGeneralError();
        }
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
     * Get all episodes for current patient.
     *
     * @return void
     */
    protected function getEpisodes(): void
    {
        try {
            $params = EncounterRequestApi::buildGetEpisodeBySearchParams(managingOrganizationId: legalEntity()->uuid);
            $this->episodes = PatientApi::getEpisodeBySearchParams($this->patientUuid, $params);
        } catch (eHealthApiException) {
            $this->flashGeneralError();
        }
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
