<?php

declare(strict_types=1);

namespace App\Livewire\Patient;

use App\Classes\eHealth\Api\PersonRequestApi;
use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Patient\Forms\Api\PatientRequestApi;
use App\Livewire\Patient\Forms\PatientForm as Form;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use App\Repositories\Repository;
use App\Traits\AddressSearch;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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
    use AddressSearch;

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
     * KEP key.
     * @var object|null
     */
    public ?object $file = null;

    /**
     * Time to resend SMS in seconds.
     * @var int
     */
    public int $resendCooldown = 60;

    public bool $showSignatureModal = false;

    public bool $showLeafletModal = false;

    public array $dictionaryNames = [
        'DOCUMENT_TYPE',
        'DOCUMENT_RELATIONSHIP_TYPE',
        'GENDER',
        'PHONE_TYPE'
    ];

    public function baseMount(): void
    {
        $this->getDictionary();
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
     */
    public function searchForPerson(): void
    {
        if (Auth::user()?->cannot('viewAny', Person::class)) {
            session()?->flash('error', 'У вас немає дозволу на пошук пацієнтів');

            return;
        }

        try {
            $this->form->rulesForModelValidate('patientsFilter');
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        $buildSearchRequest = PatientRequestApi::buildSearchForPerson($this->form->patientsFilter);
        try {
            $this->confidantPerson = Arr::toCamelCase(
                EHealth::person()->searchForPersonByParams($buildSearchRequest)->getData()
            );
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when searching for person');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when searching for person');
            session()?->flash('error', 'Виникла помилка. Спробуйте пізніше.');

            return;
        }

        $this->searchPerformed = true;
    }

    /**
     * Send API request 'Create Person v2' and show the next page if data is validated.
     *
     * @return void
     */
    public function createPerson(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            session()?->flash('error', 'У вас немає дозволу на створення пацієнта.');

            return;
        }

        $this->form->addresses = $this->address;
        $this->form->confidantPerson = $this->confidantPerson;

        try {
            $this->form->rulesForModelValidate(['patient', 'documents', 'documentsRelationship']);
            $this->form->validateBeforeSendApi();
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $formatted = $this->formatPersonRequest(removeEmptyKeys($this->form->toArray()));

        try {
            $response = EHealth::personRequest()->create(data: $formatted);

            $responseData = $response->getData();
            $responseStatusCode = $response->getStatusCode();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating person request');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating person request');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
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
                Repository::person()->savePersonResponseData($responseData, PersonRequest::class);
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Failed to store person request');
                session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

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
     */
    public function createApplication(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            session()?->flash('error', 'У вас немає дозволу на створення пацієнта.');

            return;
        }

        $this->form->addresses = $this->address;
        $this->form->confidantPerson = $this->confidantPerson;

        try {
            $this->form->rulesForModelValidate(['patient', 'documents', 'documentsRelationship']);
            $this->form->validateBeforeSendApi();
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        try {
            Repository::person()->savePersonResponseData(
                removeEmptyKeys(Arr::toSnakeCase($this->form->toArray())),
                PersonRequest::class
            );
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store person request');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        session()?->flash('success', 'Заявка на створення пацієнта успішно створена.');
        $this->redirectRoute('patient.index', [legalEntity()], navigate: true);
    }

    /**
     * Validate uploaded files and save.
     *
     * @param  string  $field
     * @return void
     */
    public function updated(string $field): void
    {
        if (str_starts_with($field, 'form.uploadedDocuments')) {
            try {
                $this->form->rulesForModelValidate('uploadedDocuments');
            } catch (ValidationException $exception) {
                session()?->flash('error', $exception->validator->errors()->first());

                return;
            }
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
     */
    public function sendFiles(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        try {
            $this->form->rulesForModelValidate('uploadedDocuments');
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        $totalFiles = count($this->form->uploadedDocuments);
        // Check that all provided files were uploaded
        if ($totalFiles !== count($this->uploadedDocuments)) {
            session()?->flash('error', 'Будь ласка завантажте всі файли!');

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
                    session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                    $this->uploadedFiles[$key] = false;
                }
            } catch (Exception) {
                session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                $this->uploadedFiles[$key] = false;
            }
        }

        // Show final status message
        if ($successCount === $totalFiles) {
            session()?->flash('success', 'Всі файли успішно завантажено');
        }

        // Approve if auth type method is offline
        if ($this->form->patient['authenticationMethods'][0]['type'] === 'OFFLINE') {
            try {
                $this->approvePersonRequest();
                $this->showLeafletModal = true;
            } catch (ConnectionException $exception) {
                $this->logConnectionError($exception, 'Error connecting when approving person request');
                session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

                return;
            } catch (EHealthValidationException|EHealthResponseException $exception) {
                $this->logEHealthException($exception, 'Error when approving person request');
                session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }
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
            session()?->flash('error', 'У вас немає дозволу на повторну відправку СМС.');

            return;
        }

        if ($this->resendCooldown > 0) {
            return;
        }

        try {
            $response = EHealth::personRequest()->resendAuthOtp($this->form->patient['id']);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when resending sms to person');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when resending sms to person');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        if ($response->getData()['status'] === 'new') {
            session()?->flash('success', 'SMS успішно надіслано!');
            $this->resendCooldown = 60;
        }
    }

    /**
     * Build and send API request 'Approve Person v2' and show the next page if data is validated.
     *
     * @return void
     */
    public function approve(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        try {
            $this->form->rulesForModelValidate('verificationCode');
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        $preRequest = ['verification_code' => $this->form->verificationCode];
        $requestData = schemaService()
            ->setDataSchema($preRequest, app(PersonRequestApi::class))
            ->requestSchemaNormalize('approveSchemaRequest')
            ->getNormalizedData();

        try {
            $this->approvePersonRequest($requestData);
            $this->showLeafletModal = true;
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when approving person request');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when approving person request');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    /**
     * Inform the patient about processing his data and show signature modal.
     *
     * @return void
     */
    public function openSignatureModal(): void
    {
        $this->showLeafletModal = false;
        $this->isInformed = true;
        $this->showSignatureModal = true;
    }

    /**
     * Build and send API request 'Sign Person v2' and redirect to page if data is validated.
     *
     * @return void
     */
    public function sign(): void
    {
        if (Auth::user()?->cannot('create', PersonRequest::class)) {
            session()?->flash('error', 'У вас немає дозволу на створення підписаного пацієнта.');

            return;
        }

        try {
            $validated = $this->form->validate($this->form->rulesForSigning());
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        try {
            $approvedPersonRequest = EHealth::personRequest()->getById($this->form->patient['id']);
            $personRequestData = $approvedPersonRequest->getData();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when getting person request by ID');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting person request by ID');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        $personRequestData['patient_signed'] = $this->isInformed;

        $signedContent = signatureService()->signData(
            $personRequestData,
            $validated['password'],
            $validated['knedp'],
            $validated['keyContainerUpload'],
            Auth::user()->party->taxId
        );

        try {
            $signResponse = EHealth::personRequest()
                ->withHeaders([
                    'msp_drfo' => Auth::user()->party->taxId
                ])
                ->signed($this->form->patient['id'], ['signed_content' => $signedContent]);
            $responseData = $signResponse->getData();
            $responseStatus = $signResponse->getStatusCode();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when sign person request');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when sign person request');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        if ($responseStatus === 200) {
            // create related person, update status
            try {
                Repository::person()->savePersonResponseData(
                    $personRequestData,
                    Person::class,
                    $responseData['person_id']
                );
                Repository::personRequest()->updateStatusByUuid($responseData);
                Repository::person()->createRelation($responseData);
            } catch (Exception|Throwable $exception) {
                $this->logDatabaseErrors($exception, $exception->getMessage());
                session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }

            session()?->flash('success', 'Пацієнт успішно створений');
            $this->redirectRoute('patient.index', [legalEntity()], navigate: true);
        }
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
        $patientData['patient']['addresses'][0] = $patientData['addresses'];

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
     * Approve person request.
     *
     * @param  array  $requestData
     * @return void
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    private function approvePersonRequest(array $requestData = []): void
    {
        $response = EHealth::personRequest()->approve($this->form->patient['id'], $requestData);
        $responseData = $response->getData();

        if ($response->getStatusCode() === 200) {
            try {
                Repository::personRequest()->updateStatusByUuid($responseData);
            } catch (Exception $exception) {
                $this->logDatabaseErrors($exception, 'Failed to update person request status');
                session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }
        }

        $this->leafletContent = $responseData['content'];
    }

    //    private function response()
    //    {
    //        return [
    //            "channel" => "MIS",
    //            "id" => "855a2863-b069-4d49-96cb-bd4bd39b6589",
    //            "patient_signed" => false,
    //            "person" => [
    //                "addresses" => [
    //                    [
    //                        "area" => "М.КИЇВ",
    //                        "building" => "33",
    //                        "country" => "UA",
    //                        "settlement" => "Київ",
    //                        "settlement_id" => "adaa4abf-f530-461c-bcbf-a0ac210d955b",
    //                        "settlement_type" => "CITY",
    //                        "street" => "Відпочинку",
    //                        "street_type" => "STREET",
    //                        "type" => "RESIDENCE",
    //                        "zip" => "31233"
    //                    ]
    //                ],
    //                "authentication_methods" => [["type" => "OFFLINE"]],
    //                "birth_country" => "Юар",
    //                "birth_date" => "2000-09-08",
    //                "birth_settlement" => "Міс",
    //                "documents" => [
    //                    [
    //                        "expiration_date" => "2030-09-08",
    //                        "issued_at" => "2001-09-08",
    //                        "issued_by" => "Орган",
    //                        "number" => "ВН123321",
    //                        "type" => "PASSPORT"
    //                    ]
    //                ],
    //                "email" => "emailTest@gmail.com",
    //                "emergency_contact" => [
    //                    "first_name" => "Михайло",
    //                    "last_name" => "Груша",
    //                    "phones" => [
    //                        ["number" => "+380213213213", "type" => "MOBILE"],
    //                        ["number" => "+380331231212", "type" => "LAND_LINE"]
    //                    ]
    //                ],
    //                "first_name" => "Соломія",
    //                "gender" => "FEMALE",
    //                "last_name" => "Забарна",
    //                "no_tax_id" => false,
    //                "phones" => [
    //                    ["number" => "+380222222222", "type" => "MOBILE"],
    //                ],
    //                "secret" => "словоо",
    //                "tax_id" => "1234567812",
    //                "process_disclosure_data_consent" => true,
    //                "status" => "NEW",
    //                "dbId" => 4
    //            ]
    //        ];
    //    }
}
