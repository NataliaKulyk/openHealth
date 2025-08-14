<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Livewire\Declaration\Forms\DeclarationForm as Form;
use App\Models\DeclarationRequest;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

class DeclarationCreate extends DeclarationComponent
{
    use WithFileUploads;

    public Form $form;

    #[Locked]
    public int $patientId;

    public bool $showAuthModal = false;
    public bool $showSignModal = false;
    public bool $showSignatureModal = false;
    public bool $showUploadingDocumentsModal = false;

    public $uploadedDocuments;

    /**
     * Patient full name.
     * @var string
     */
    public string $patientFullName;

    /**
     * List of patient authentication methods.
     * @var array
     */
    public array $authMethods;

    /**
     * UUID of created declaration request.
     * @var string
     */
    public string $declarationRequestUuid;

    public array $dictionaryNames = ['POSITION'];

    public array $employeesInfo;

    public bool $smsResent = false;

    /**
     * Content that formatted by eHealth that we propose to print.
     * @var string
     */
    public string $printableContent;

    /**
     * Check is patient sign form.
     * @var bool
     */
    public bool $isSigned = true;

    public array $dataToBeSigned;

    /**
     * Patient UUID, used for eHeath request.
     * @var string
     */
    protected string $patientUuid;

    public function mount(LegalEntity $legalEntity, int $patientId): void
    {
        $this->patientId = $patientId;
        $this->getDictionary();

        $patient = Person::select(['uuid', 'first_name', 'last_name', 'second_name'])
            ->where('id', $this->patientId)
            ->firstOrFail();
        $this->patientFullName = $patient->fullName;
        $this->patientUuid = $patient->uuid;

        $this->setEmployeesInfo();

        $this->form->personId = $this->patientUuid;
        $this->authMethods = $this->getPersonAuthMethods();
    }

    /**
     * Create a validated application(declaration request).
     *
     * @return void
     */
    public function create(): void
    {
        if (Auth::user()?->cannot('create', DeclarationRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на створення заявки на подання декларації.',
                'type' => 'error'
            ]);

            return;
        }

        if (empty($this->form->divisionId)) {
            $this->form->divisionId = optional(
                collect($this->employeesInfo)->firstWhere('employeeId', $this->form->employeeId)
            )['divisionId'] ?? '';
        }

        try {
            $validated = $this->form->validate($this->form->rulesForCreating());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::declarationRequest()->create(data: removeEmptyKeys(Arr::toSnakeCase($validated)));

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while creating declaration request');
                $this->flashGeneralError();

                return;
            }

            if ($response->getStatusCode() === 200) {
                $this->declarationRequestUuid = $response->getData()['id'];

                if ($response->getUrgent()['authentication_method_current']['type'] === 'OFFLINE') {
                    $this->uploadedDocuments = $response->getUrgent()['documents'];
                    $this->showUploadingDocumentsModal = true;
                }

                if ($response->getUrgent()['authentication_method_current']['type'] === 'OTP') {
                    $this->showAuthModal = true;
                }
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while creating declaration request');
            $this->flashGeneralError();

            return;
        }
    }

    /**
     * Validate uploaded files.
     *
     * @param  string  $field
     * @return void
     */
    public function updated(string $field): void
    {
        if (str_starts_with($field, 'form.uploadedDocuments')) {
            $this->form->validate($this->form->rulesForUploadingDocuments());
        }
    }

    /**
     * Upload patient files to the appropriate URL.
     *
     * @return void
     * @throws ValidationException
     */
    public function sendFiles(): void
    {
        try {
            $this->form->validate($this->form->rulesForUploadingDocuments());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        $totalFiles = count($this->form->uploadedDocuments);
        // Check that all provided files were uploaded
        if ($totalFiles !== count($this->uploadedDocuments)) {
            $this->dispatch('flashMessage', [
                'message' => 'Будь ласка завантажте всі файли!',
                'type' => 'error'
            ]);

            return;
        }

        $successCount = 0;
        foreach ($this->form->uploadedDocuments as $key => $document) {
            try {
                $response = EHealth::declarationRequest()->uploadDocument(
                    $this->uploadedDocuments[$key]['url'],
                    $document
                );

                if ($response->getStatusCode() === 200) {
                    $successCount++;
                } else {
                    $this->flashGeneralError();
                }
            } catch (ConnectionException) {
                $this->flashGeneralError();
            }
        }

        // Approve if all files were uploaded successfully
        if ($successCount === $totalFiles) {
            $this->approveUploadedFiles();
        }
    }

    /**
     * Resend SMS to patient.
     *
     * @return void
     */
    public function resendSms(): void
    {
        if ($this->smsResent) {
            $this->dispatch('flashMessage', [
                'message' => __('СМС вже відправлено повторно. Виконати повторне надсилання можна лише разово.'),
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::declarationRequest()->resendAuthOtp($this->declarationRequestUuid);

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while resending auth OTP on declaration request');
                $this->flashGeneralError();

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while resending sms to person');
            $this->flashGeneralError();

            return;
        }

        if ($response->getData()['status'] === 'new') {
            $this->smsResent = true;
            $this->dispatch('flashMessage', [
                'message' => __('SMS успішно надіслано!'),
                'type' => 'success'
            ]);
        }
    }

    /**
     * Send approving request with verified code.
     *
     * @return void
     */
    public function approve(): void
    {
        if (Auth::user()?->cannot('approve', DeclarationRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на підтвердження заявки на подання декларації.',
                'type' => 'error'
            ]);

            return;
        }

        try {
            $validated = $this->form->validate($this->form->rulesForApproving());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::declarationRequest()
                ->approve($this->declarationRequestUuid, Arr::toSnakeCase($validated));

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while approving declaration request');
                $this->flashGeneralError();

                return;
            }

            if ($response->getStatusCode() === 200) {
                $this->printableContent = $response->getData()['data_to_be_signed']['content'];
                $this->dataToBeSigned = $response->getData()['data_to_be_signed'];
                $this->showAuthModal = false;
                $this->showSignModal = true;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while approving declaration request');
            $this->flashGeneralError();

            return;
        }
    }

    public function openSignatureModal(): void
    {
        $this->showSignModal = false;
        $this->showSignatureModal = true;
    }

    /**
     * Sign declaration request with Cipher and then send to EHealth.
     *
     * @return void
     */
    public function sign(): void
    {
        if (Auth::user()?->cannot('sign', DeclarationRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на підписання заявки на подання декларації.',
                'type' => 'error'
            ]);

            return;
        }

        try {
            $validated = $this->form->validate($this->form->rulesForSigning());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        $dataToSign = $this->dataToBeSigned;
        $dataToSign['person']['patient_signed'] = $this->isSigned;

        $signedContent = signatureService()->signData(
            $dataToSign,
            $validated['password'],
            $validated['knedp'],
            $validated['keyContainerUpload'],
            Auth::user()->party->taxId
        );

        try {
            $response = EHealth::declarationRequest()->sign(
                $this->declarationRequestUuid,
                ['signed_declaration_request' => $signedContent]
            );

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while signing declaration request');
                $this->flashGeneralError();

                return;
            }

            if ($response->getStatusCode() === 200) {
                to_route('declaration.index', [legalEntity()])->with('flashMessage', [
                    'message' => 'Декларація підписана',
                    'type' => 'success'
                ]);
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while signing declaration request');
            $this->flashGeneralError();

            return;
        }
    }

    /**
     * Get all employees with divisions and set that info.
     *
     * @return void
     */
    protected function setEmployeesInfo(): void
    {
        $employees = Auth::user()?->employees()->whereNotNull('division_id')->with('division')->get();
        $this->employeesInfo = $employees->map(static fn (Employee $employee) => [
            'employeeId' => $employee->uuid,
            'fullName' => $employee->fullName,
            'position' => $employee->position,
            'divisionId' => $employee->division->uuid,
            'divisionName' => $employee->division->name
        ])->toArray();

        if (count($this->employeesInfo) === 1) {
            $this->form->employeeId = $this->employeesInfo[0]['employeeId'];
            $this->form->divisionId = $this->employeesInfo[0]['divisionId'];
        }
    }

    /**
     * Get patient authentication methods.
     *
     * @return array
     */
    protected function getPersonAuthMethods(): array
    {
        try {
            $response = EHealth::person()->getAuthMethods($this->patientUuid);

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while getting patient auth methods');
                $this->flashGeneralError();

                return [];
            }

            return $response->getData();
        } catch (ConnectionException $e) {
            Log::channel('e_health_errors')->error('Error while getting auth methods', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->flashGeneralError();

            return [];
        }
    }

    /**
     * Send approve request if all files were uploaded successfully
     *
     * @return void
     */
    protected function approveUploadedFiles(): void
    {
        if (Auth::user()?->cannot('approve', DeclarationRequest::class)) {
            $this->dispatch('flashMessage', [
                'message' => 'У вас немає дозволу на створення заявки на подання декларації.',
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::declarationRequest()->approve($this->declarationRequestUuid);

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while approving declaration request after sending files');
                $this->flashGeneralError();

                return;
            }

            if ($response->getStatusCode() === 200) {
                $this->printableContent = $response->getData()['data_to_be_signed']['content'];
                $this->dataToBeSigned = $response->getData()['data_to_be_signed'];
                $this->showUploadingDocumentsModal = false;
                $this->showSignModal = true;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while approving declaration request after sending files');
            $this->flashGeneralError();

            return;
        }
    }
}
