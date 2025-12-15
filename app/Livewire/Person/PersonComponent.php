<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use App\Classes\eHealth\Api\PersonRequestApi;
use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Person\Forms\PersonForm as Form;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use App\Repositories\Repository;
use App\Traits\Addresses\AddressSearch;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class PersonComponent extends Component
{
    use FormTrait;
    use WithFileUploads;
    use AddressSearch;

    #[Locked]
    public int $personId;

    public string $mode = 'create';

    public Form $form;

    /**
     * List of founded confidant person.
     *
     * @var array
     */
    public array $confidantPerson = [];

    /**
     * List of uploaded documents.
     *
     * @var array
     */
    public array $uploadedDocuments = [];

    /**
     * Content that shows to the patient when signing the leaflet.
     *
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
     *
     * @var string|null
     */
    public ?string $selectedConfidantPersonId = null;

    /**
     * Show different frontend base on mode.
     *
     * @var string
     */
    public string $viewState = 'default';

    public bool $showAdditionalParams;

    /**
     * Track uploaded files.
     *
     * @var array
     */
    public array $uploadedFiles = [];

    /**
     * Mark 'information from the leaflet was communicated to the patient'.
     *
     * @var bool
     */
    public bool $isInformed = false;

    /**
     * Is patient incapable or child less than 14 y.o.
     *
     * @var bool
     */
    public bool $isIncapacitated = false;

    /**
     * KEP key.
     *
     * @var object|null
     */
    public ?object $file = null;

    /**
     * Time to resend SMS in seconds.
     *
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
        $personData = collect($this->confidantPerson)->firstWhere('id', $id);

        if ($personData) {
            $this->selectedConfidantPersonId = $id;
            $this->confidantPerson = [$personData];
            $this->form->person['authenticationMethods'][0]['value'] = $personData['id'];
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
        $this->form->person['authenticationMethods'][0]['value'] = null;

        $this->confidantPerson = [];
        $this->selectedConfidantPersonId = null;
        $this->searchPerformed = false;
    }

    /**
     * Search for person with provided filters.
     *
     * @return void
     */
    public function searchForPerson(): void
    {
        if (Auth::user()->cannot('viewAny', Person::class)) {
            Session::flash('error', 'У вас немає дозволу на пошук пацієнтів');

            return;
        }

        try {
            $validated = $this->form->validate($this->form->rulesForSearch());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $buildSearchRequest = removeEmptyKeys(Arr::toSnakeCase($validated));
        try {
            $this->confidantPerson = Arr::toCamelCase(
                EHealth::person()->searchForPersonByParams($buildSearchRequest)->getData()
            );
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when searching for person');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when searching for person');
            Session::flash('error', 'Виникла помилка. Спробуйте пізніше.');

            return;
        }

        $this->searchPerformed = true;
    }

    /**
     * Send API request 'Create Person v2' and show the next page if data is validated.
     *
     * @return void
     */
    public function create(): void
    {
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на створення пацієнта.');

            return;
        }

        $this->form->addresses = $this->address;
        $this->form->confidantPerson = $this->confidantPerson;

        try {
            $validated = $this->form->rulesForModelValidate(['person', 'documents', 'documentsRelationship']);
            $this->form->validateBeforeSendApi();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $formatted = $this->form->formatForApi(array_merge($validated, ['addresses' => $this->form->addresses], ['confidantPerson' => $this->form->confidantPerson]));

        try {
            $response = EHealth::personRequest()->create($formatted);

            $responseData = $response->getData();
            $responseStatusCode = $response->getStatusCode();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating person request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating a person request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        if ($responseStatusCode === 201) {
            if (isset($this->personId)) {
                $responseData['dbId'] = $this->personId;
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
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }

            $this->form->person['id'] = $responseData['id'];
            $this->uploadedDocuments = $response->getUrgent()['documents'];
            $this->viewState = 'new';
        }
    }

    /**
     * Create data about person request in DB.
     *
     * @return void
     */
    public function createLocally(): void
    {
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на створення пацієнта.');

            return;
        }

        $this->form->addresses = $this->address;
        $this->form->confidantPerson = $this->confidantPerson;

        try {
            $this->form->rulesForModelValidate(['person', 'documents', 'documentsRelationship']);
            $this->form->validateBeforeSendApi();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            Repository::person()->savePersonResponseData(
                removeEmptyKeys(Arr::toSnakeCase($this->form->toArray())),
                PersonRequest::class
            );
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store person request');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        Session::flash('success', 'Заявка на створення пацієнта успішно створена.');
        $this->redirectRoute('persons.index', [legalEntity()], navigate: true);
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
                $this->form->validate($this->form->rulesForFiles());
            } catch (ValidationException $exception) {
                Session::flash('error', $exception->validator->errors()->first());
                $this->setErrorBag($exception->validator->getMessageBag());

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
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        try {
            $this->form->validate($this->form->rulesForFiles());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $totalFiles = count($this->form->uploadedDocuments);
        // Check that all provided files were uploaded
        if ($totalFiles !== count($this->uploadedDocuments)) {
            Session::flash('error', 'Будь ласка завантажте всі файли!');

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
                    Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                    $this->uploadedFiles[$key] = false;
                }
            } catch (Exception) {
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                $this->uploadedFiles[$key] = false;
            }
        }

        // Show final status message
        if ($successCount === $totalFiles) {
            Session::flash('success', 'Всі файли успішно завантажено');
        }

        // Approve if auth type method is offline
        if ($this->form->person['authenticationMethods'][0]['type'] === 'OFFLINE') {
            try {
                $this->approvePersonRequest();
                $this->showLeafletModal = true;
            } catch (ConnectionException $exception) {
                $this->logConnectionError($exception, 'Error connecting when approving person request');
                Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

                return;
            } catch (EHealthValidationException|EHealthResponseException $exception) {
                $this->logEHealthException($exception, 'Error when approving person request');

                if ($exception instanceof EHealthValidationException) {
                    Session::flash('error', $exception->getFormattedMessage());
                } else {
                    Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
                }

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
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на повторну відправку СМС.');

            return;
        }

        if ($this->resendCooldown > 0) {
            return;
        }

        try {
            $response = EHealth::personRequest()->resendAuthOtp($this->form->person['id']);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when resending sms to person');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when resending sms to person');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        if ($response->getData()['status'] === 'new') {
            Session::flash('success', 'SMS успішно надіслано!');
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
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        try {
            $validated = $this->form->validate($this->form->rulesForApprove());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        $preRequest = ['verification_code' => $validated];
        $requestData = schemaService()
            ->setDataSchema($preRequest, app(PersonRequestApi::class))
            ->requestSchemaNormalize('approveSchemaRequest')
            ->getNormalizedData();

        try {
            $this->approvePersonRequest($requestData);
            $this->showLeafletModal = true;
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when approving person request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when approving person request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

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
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на створення підписаного пацієнта.');

            return;
        }

        try {
            $validated = $this->form->validate($this->form->signingRules());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            $approvedPersonRequest = EHealth::personRequest()->getById($this->form->person['id']);
            $personRequestData = $approvedPersonRequest->getData();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when getting person request by ID');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting person request by ID');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

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
                ->signed($this->form->person['id'], ['signed_content' => $signedContent]);
            $responseData = $signResponse->getData();
            $responseStatus = $signResponse->getStatusCode();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when sign person request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when sign person request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

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
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }

            Session::flash('success', 'Пацієнт успішно створений');
            $this->redirectRoute('persons.index', [legalEntity()], navigate: true);
        }
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
        $response = EHealth::personRequest()->approve($this->form->person['id'], $requestData);
        $responseData = $response->getData();

        if ($response->getStatusCode() === 200) {
            try {
                Repository::personRequest()->updateStatusByUuid($responseData);
            } catch (Exception $exception) {
                $this->logDatabaseErrors($exception, 'Failed to update person request status');
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }
        }

        $this->leafletContent = $responseData['content'];
    }
}
