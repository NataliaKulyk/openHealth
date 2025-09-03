<?php

declare(strict_types=1);

namespace App\Livewire\Patient;

use App\Classes\Cipher\Traits\Cipher;
use App\Classes\eHealth\Api\PersonApi;
use App\Classes\eHealth\Api\PersonRequestApi;
use App\Classes\eHealth\EHealth;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Core\Arr;
use App\Livewire\Patient\Forms\Api\PatientRequestApi;
use App\Livewire\Patient\Forms\PatientForm as Form;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use App\Repositories\PersonRepository;
use App\Traits\AddressSearch;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class PatientComponent extends Component
{
    use FormTrait;
    use WithFileUploads;
    use Cipher;
    use AddressSearch;

    /**
     * Allowed model modals name.
     */
    private const array ALLOWED_MODAL_MODELS = [
        'signedContent'
    ];

    #[Locked]
    public int $patientId;

    public string $mode = 'create';

    public Form $form;

    /**
     * List of founded confidant person.
     * @var array
     */
    public array $confidantPerson = [];

    /**
     * List of uploaded documents.
     * @var array
     */
    public array $uploadedDocuments = [];

    /**
     * Content that shows to the patient when signing the leaflet.
     * @var string
     */
    public string $leafletContent;

    /**
     * Check if the search person's request found someone.
     *
     * @var bool
     */
    public bool $searchPerformed = false;

    /**
     * ID selected confidant person.
     * @var string|null
     */
    public ?string $selectedConfidantPatientId = null;

    /**
     * Show different frontend base on mode.
     * @var string
     */
    public string $viewState = 'default';

    /**
     * Track uploaded files.
     * @var array
     */
    public array $uploadedFiles = [];

    /**
     * Check is files was successfully uploaded.
     * @var bool
     */
    public bool $isUploaded = false;

    /**
     * Mark 'information from the leaflet was communicated to the patient'.
     * @var bool
     */
    public bool $isInformed = false;

    /**
     * Is patient incapable or child less than 14 y.o.
     * @var bool
     */
    public bool $isIncapacitated = false;

    /**
     * Check is person approved.
     * @var bool
     */
    public bool $isApproved = false;

    /**
     * KEP key.
     * @var object|null
     */
    public ?object $file = null;

    /**
     * Time to resend SMS in seconds.
     * @var int
     */
    public int $resendCooldown = 60;

    public array $dictionaryNames = [
        'DOCUMENT_TYPE',
        'DOCUMENT_RELATIONSHIP_TYPE',
        'GENDER',
        'PHONE_TYPE'
    ];

    public function mount(LegalEntity $legalEntity, ?int $id = null): void
    {
        if ($id !== null) {
            $fromDatabase = PersonRequest::find($id, ['id']);

            // Make sure the ID in the URL matches the patient's ID.
            if ($fromDatabase?->id !== $id) {
                abort(403);
            }

            $this->patientId = $id;
            $this->checkIfIncapacitated();
        }

        $this->getPatient();
        $this->setCertificateAuthority();
        $this->getDictionary();
    }

    /**
     * Initialize the creation mode for a specific model.
     *
     * @param  string  $model  The model type to initialize for creation.
     * @return void
     * @throws ValidationException
     */
    public function create(string $model): void
    {
        $this->validateModel($model);

        $this->mode = 'create';
        $this->form->{$model} = [];
        $this->openModal($model);
    }

    /**
     * Choose a confidant person from the provided list.
     *
     * @param  string  $id
     * @return void
     */
    public function chooseConfidantPerson(string $id): void
    {
        $patientData = collect($this->confidantPerson)->firstWhere('id', $id);

        if ($patientData) {
            $this->selectedConfidantPatientId = $id;
            $this->confidantPerson = [$patientData];
            $this->form->patient['authenticationMethods'][0]['value'] = $patientData['id'];
        }

        $this->searchPerformed = true;
    }

    /**
     * Remove selected confidant person from the cache and form.
     *
     * @return void
     */
    public function removeConfidantPerson(): void
    {
        $this->form->patient['authenticationMethods'][0]['value'] = null;

        $this->confidantPerson = [];
        $this->selectedConfidantPatientId = null;
        $this->searchPerformed = false;
    }

    /**
     * Search for person with provided filters.
     *
     * @return void
     * @throws ApiException|ValidationException
     */
    public function searchForPerson(): void
    {
        $this->form->rulesForModelValidate('patientsFilter');

        $buildSearchRequest = PatientRequestApi::buildSearchForPerson($this->form->patientsFilter);

        $this->confidantPerson = Arr::toCamelCase(PersonApi::searchForPersonByParams($buildSearchRequest));
        $this->searchPerformed = true;
    }

    /**
     * Send API request 'Create Person v2' and show the next page if data is validated.
     *
     * @return void
     * @throws ValidationException
     */
    public function createPerson(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на створення пацієнта.',
                'type' => 'error'
            ]);

            return;
        }

        $this->preparePersonRequest();
        $this->validatePersonRequest(['patient', 'documents', 'documentsRelationship']);

        $formatted = $this->formatPersonRequest(removeEmptyKeys($this->form->toArray()));

        try {
            $response = EHealth::personRequest()->create(data: $formatted);

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while creating person request');
                $this->flashGeneralError();

                return;
            }

            $responseData = $response->getData();
            $responseStatusCode = $response->getStatusCode();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while creating person request');
            $this->flashGeneralError();

            return;
        }

        if ($responseStatusCode !== 201) {
            $this->flashGeneralError();
        }

        if ($responseStatusCode === 201) {
            if (isset($this->patientId)) {
                $responseData['dbId'] = $this->patientId;
            }

            if (isset($responseData['person']['confidant_person'])) {
                $responseData['person']['confidant_person']['confidantPersonInfo'] = Arr::toSnakeCase(
                    $this->confidantPerson[0]
                );
            }

            // save in DB
            try {
                PersonRepository::savePersonResponseData($responseData, PersonRequest::class);
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Failed to store person request');
                $this->flashGeneralError();

                return;
            }

            $this->form->patient['id'] = $responseData['id'];
            $this->uploadedDocuments = $response->getUrgent()['documents'];
            $this->viewState = 'new';
        }
    }

    /**
     * Create data about person request in DB.
     *
     * @return void
     * @throws ValidationException
     */
    public function createApplication(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на створення пацієнта.',
                'type' => 'error'
            ]);

            return;
        }

        $this->preparePersonRequest();
        $this->validatePersonRequest(['patient', 'documents', 'documentsRelationship']);

        try {
            PersonRepository::savePersonResponseData(
                removeEmptyKeys(Arr::toSnakeCase($this->form->toArray())),
                PersonRequest::class
            );
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store person request');
            $this->flashGeneralError();

            return;
        }

        to_route('patient.index', [legalEntity()])->with('flashMessage', [
            'message' => 'Пацієнт успішно створений',
            'type' => 'success'
        ]);
    }

    /**
     * Validate uploaded files and save.
     *
     * @param  string  $field
     * @return void
     * @throws ValidationException
     */
    public function updated(string $field): void
    {
        if (str_starts_with($field, 'form.uploadedDocuments')) {
            $this->form->rulesForModelValidate('uploadedDocuments');
        }
    }

    /**
     * Delete uploaded file.
     *
     * @param  int  $key
     * @return void
     */
    public function deleteDocument(int $key): void
    {
        unset($this->form->uploadedDocuments[$key]);
    }

    /**
     * Upload patient files to the appropriate URL.
     *
     * @return void
     * @throws ValidationException
     */
    public function sendFiles(): void
    {
        $this->form->rulesForModelValidate('uploadedDocuments');

        $totalFiles = count($this->form->uploadedDocuments);
        // Check that all provided files were uploaded
        if ($totalFiles !== count($this->uploadedDocuments)) {
            $this->dispatch('flashMessage', [
                'message' => 'Будь ласка завантажте всі файли!',
                'type' => 'error',
            ]);

            return;
        }

        $successCount = 0;
        foreach ($this->form->uploadedDocuments as $key => $document) {
            try {
                $filePath = $document->getRealPath();
                $fileMime = $document->getMimeType();
                $fileContents = file_get_contents($filePath);
                $uploadUrl = trim($this->uploadedDocuments[$key]['url']);

                $uploadResponse = Http::withHeaders([
                    'Content-Type' => $fileMime,
                ])->withBody($fileContents, $fileMime)->put($uploadUrl);

                if ($uploadResponse->getStatusCode() === 200) {
                    $successCount++;

                    $this->uploadedFiles[$key] = true;
                } else {
                    $this->flashGeneralError();

                    $this->uploadedFiles[$key] = false;
                }
            } catch (Exception) {
                $this->flashGeneralError();

                $this->uploadedFiles[$key] = false;
            }
        }

        // Show final status message
        if ($successCount === $totalFiles) {
            $this->isUploaded = true;

            $this->dispatch('flashMessage', [
                'message' => 'Всі файли успішно завантажено',
                'type' => 'success',
            ]);
        }

        // Approve if auth type method is offline
        if ($this->form->patient['authenticationMethods'][0]['type'] === 'OFFLINE') {
            $this->approvePersonRequest();
        }
    }

    /**
     * Show translated documents name.
     *
     * @param  array  $document
     * @return string
     */
    public function getDocumentLabel(array $document): string
    {
        return __('patients.documents.' . Str::lower(Str::afterLast($document['type'], '.')));
    }

    /**
     * Resend SMS with confirmation code.
     *
     * @return void
     */
    public function resendSms(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на повторну відправку СМС.',
                'type' => 'error'
            ]);

            return;
        }

        if ($this->resendCooldown > 0) {
            return;
        }

        try {
            $response = EHealth::personRequest()->resendAuthOtp($this->form->patient['id']);
        } catch (ConnectionException $e) {
            Log::channel('e_health_errors')->error('Error while resending sms to person request', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->flashGeneralError();

            return;
        }

        if ($response->getData()['status'] === 'new') {
            $this->dispatch('flashMessage', [
                'message' => __('SMS успішно надіслано!'),
                'type' => 'success'
            ]);

            $this->resendCooldown = 60;
        }
    }

    /**
     * Build and send API request 'Approve Person v2' and show the next page if data is validated.
     *
     * @return void
     * @throws ValidationException
     */
    public function approvePerson(): void
    {
        $this->form->rulesForModelValidate('verificationCode');

        $preRequest = ['verification_code' => (int)$this->form->verificationCode];
        $requestData = schemaService()
            ->setDataSchema($preRequest, app(PersonRequestApi::class))
            ->requestSchemaNormalize('approveSchemaRequest')
            ->getNormalizedData();

        $this->approvePersonRequest($requestData);
    }

    /**
     * Inform the patient about processing his data and close the modal.
     *
     * @return void
     */
    public function informAndCloseModal(): void
    {
        $this->isInformed = true;
        $this->isApproved = true;
        $this->closeModalModel();
    }

    public function updatedFile(): void
    {
        $this->keyContainerUpload = $this->file;
    }

    /**
     * Build and send API request 'Sign Person v2' and redirect to page if data is validated.
     *
     * @return void
     */
    public function sign(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на створення підписаного пацієнта.',
                'type' => 'error'
            ]);

            return;
        }

        try {
            $approvedPersonRequest = EHealth::personRequest()->getById($this->form->patient['id']);
            $personRequestData = $approvedPersonRequest->getData();
        } catch (ConnectionException $e) {
            Log::channel('e_health_errors')->error('Error while getting person request by ID', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->flashGeneralError();

            return;
        }
        $personRequestData['patient_signed'] = $this->isInformed;

        // Encrypt data
        $encryptedRequestData = schemaService()
            ->setDataSchema($personRequestData, app(PersonRequestApi::class))
            ->requestSchemaNormalize('encryptSignSchemaRequest')
            ->getNormalizedData();

        $base64EncryptedData = $this->sendEncryptedData($encryptedRequestData, Auth::user()->party->taxId);

        // sign person request
        $preRequest = ['signed_content' => $base64EncryptedData];
        $signRequestData = schemaService()
            ->setDataSchema($preRequest, app(PersonRequestApi::class))
            ->requestSchemaNormalize('signSchemaRequest')
            ->getNormalizedData();

        try {
            $signResponse = EHealth::personRequest()
                ->withHeaders([
                    'msp_drfo' => Auth::user()->party->taxId
                ])
                ->signed($this->form->patient['id'], $signRequestData);
            $responseData = $signResponse->getData();
            $responseStatus = $signResponse->getStatusCode();
        } catch (ConnectionException $e) {
            Log::channel('e_health_errors')->error('Error while getting person request by ID', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->flashGeneralError();

            return;
        }

        if ($responseStatus === 200) {
            // create related person, update status
            try {
                PersonRepository::savePersonResponseData(
                    $personRequestData,
                    Person::class,
                    $responseData['person_id']
                );
                PersonRepository::updatePersonRequestStatusByUuid($responseData);
                PersonRepository::createRelation($responseData);
            } catch (Exception|Throwable $e) {
                Log::channel('db_errors')->error('Failed to finalize person creation', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                $this->flashGeneralError();

                return;
            }

            to_route('patient.index', [legalEntity()])->with('flashMessage', [
                'message' => 'Пацієнт успішно створений',
                'type' => 'success'
            ]);
        }
    }

    /**
     * Check if the patient has a related confidant person.
     *
     * @return void
     */
    protected function checkIfIncapacitated(): void
    {
        $this->isIncapacitated = PersonRequest::where('id', $this->patientId)
            ->whereHas('confidantPerson')
            ->exists();
    }

    /**
     * Get all data about the patient from the DB.
     *
     * @return void
     */
    protected function getPatient(): void
    {
        if (isset($this->patientId)) {
            $patientData = PersonRequest::showPersonRequest($this->patientId)->first();

            // Format data
            $result = [
                'patient' => array_merge($patientData->toArray(), [
                    'phones' => count($patientData->phones) === 0
                        ? [['type' => null, 'number' => null]]
                        : $patientData->phones->toArray(),
                    'authentication_methods' => $patientData->authenticationMethod->toArray()
                ]),
                'documents' => $patientData->documents->toArray(),
                'address' => $patientData->address->toArray(),
                'confidantPerson' => $patientData->confidantPerson?->toArray() ?? []
            ];

            $result = Arr::toCamelCase($result);
            $this->form->fill($result);
            $this->address = $result['address'];
            $this->confidantPerson = $result['confidantPerson'] ?? [];
        }
    }

    /**
     * Get Certificate Authority from API.
     *
     * @return array
     * @throws \App\Classes\Cipher\Exceptions\ApiException
     */
    private function setCertificateAuthority(): array
    {
        return $this->getCertificateAuthority = $this->getCertificateAuthority();
    }

    /**
     * Build API request for create person.
     *
     * @param  array  $patientData
     * @return array
     */
    protected function formatPersonRequest(array $patientData): array
    {
        $patientData['patient']['documents'] = $patientData['documents'];
        $patientData['patient']['addresses'][] = $patientData['addresses'];

        if (isset($patientData['patient']['id'])) {
            unset($patientData['patient']['id']);
        }

        if (!empty($patientData['confidantPerson'])) {
            $patientData['patient']['confidantPerson']['personId'] = $patientData['confidantPerson'][0]['personUuid'] ?? $patientData['confidantPerson'][0]['id'];
            $patientData['patient']['confidantPerson']['documentsRelationship'] = $patientData['documentsRelationship'];
        }

        $preRequest = schemaService()
            ->setDataSchema(['person' => $patientData['patient']], app(PersonRequestApi::class))
            ->requestSchemaNormalize()
            ->getNormalizedData();

        $preRequest['patient_signed'] = $this->isInformed;
        $preRequest['process_disclosure_data_consent'] = true;

        return $preRequest;
    }

    /**
     * Prepare person request data.
     *
     * @return void
     */
    private function preparePersonRequest(): void
    {
        $this->form->addresses = $this->address;
        $this->form->confidantPerson = $this->confidantPerson;
    }

    /**
     * Validate person request data.
     *
     * @param  array  $models
     * @throws ValidationException
     */
    private function validatePersonRequest(array $models): void
    {
        try {
            $this->form->rulesForModelValidate($models);
            $this->form->validateBeforeSendApi();
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            throw $e;
        }
    }

    /**
     * Approve person request.
     *
     * @param  array  $requestData
     * @return void
     */
    private function approvePersonRequest(array $requestData = []): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на підтвердження пацієнта.',
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::personRequest()->approve($this->form->patient['id'], $requestData);
            $responseData = $response->getData();
            $responseStatus = $response->getStatusCode();
        } catch (ConnectionException $e) {
            Log::channel('e_health_errors')->error('Error while approving person request', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->flashGeneralError();

            return;
        }

        if ($responseStatus === 200) {
            try {
                PersonRepository::updatePersonRequestStatusByUuid($responseData);
            } catch (Exception $e) {
                Log::channel('db_errors')->error('Failed to update person request status', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                $this->flashGeneralError();

                return;
            }

            $this->isApproved = true;
            $this->leafletContent = $responseData['content'];
            $this->openModal('patientLeaflet');
        }
    }

    /**
     * Validate model name from modals.
     *
     * @param  string  $model
     * @return void
     * @throws ValidationException
     */
    private function validateModel(string $model): void
    {
        if (!in_array($model, self::ALLOWED_MODAL_MODELS, true)) {
            $this->dispatch('flashMessage', [
                'message' => 'Недопустиме значення моделі',
                'type' => 'error'
            ]);

            throw ValidationException::withMessages([
                'model' => 'Недопустиме значення моделі'
            ]);
        }
    }

    public function render(): View
    {
        return view('livewire.patient.patient-form');
    }
}
