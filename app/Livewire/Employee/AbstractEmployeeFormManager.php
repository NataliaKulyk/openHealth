<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Classes\eHealth\Api\EmployeeRequest as EHealthEmployeeRequest;
use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\Employee\BaseEmployee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Revision;
use Auth;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

abstract class AbstractEmployeeFormManager extends EmployeeComponent
{
    use WithFileUploads;

    #[Locked]
    public ?int $employeeRequestId = null;
    protected ?BaseEmployee $employeeRequest;
    protected ?BaseEmployee $employee = null;

    /**
     * Спеціальний прапор для PartyEdit, який блокує лише ПІБ, дату народження і ІПН,
     * але залишає телефони, email тощо активними.
     */
    public bool $isPartyDataPartiallyLocked = false;

    /**
     * Колекція користувачів (User) для Party,
     * використовується у 'position_add' для вибору email.
     */
    public ?Collection $partyUsers = null;

    /**
     * Email, обраний у випадаючому списку в 'position_add'.
     * Ми НЕ МОЖЕМО використовувати 'form.party.email', бо він вже зайнятий.
     */
    public ?string $formEmail = null;

    /**
     * Колекція існуючих посад для Party,
     * використовується у 'party_edit' для відображення списку.
     */
    public ?Collection $partyExistingPositions = null;

    public string $pageTitle = '';

    // === PUBLIC ACTIONS ===
    // These methods define the shared algorithm. They call the abstract method.
    public function save(): void
    {
        try {
            // The validation call is now dynamic
            $this->form->validate($this->form->rulesForSave($this));

            $this->employeeRequest = $this->handleDraftPersistence();
            $this->employeeRequestId = $this->employeeRequest->id;

            $this->dispatch('flashMessage', ['message' => __('forms.employee_request_saved_successfully'), 'type' => 'success']);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (Exception $e) {
            $this->handleGeneralException($e);
        }
    }

    // Used by resources/views/livewire/employee/party.blade.php
    public function prepareForSigning(): void
    {
        try {
            $this->form->validate($this->form->rulesForSave($this));
            $this->employeeRequest = $this->handleDraftPersistence();
            $this->employeeRequestId = $this->employeeRequest->id;

            // Now dispatch the events
            $this->dispatch(
                'flashMessage',
                ['message' => __('forms.employee_request_saved_successfully'), 'type' => 'success']
            );
            $this->dispatch('open-signature-modal');
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (Exception $e) {
            $this->handleGeneralException($e);
        }
    }

    public function sign()
    {
        Log::info('Attempting to sign.');

        try {
            // 1. Validate the form
            $this->form->validate($this->form->rulesForSave($this));
            // 2. Persist the draft using the component's specific logic
            $this->employeeRequest = $this->handleDraftPersistence();
            $this->employeeRequestId = $this->employeeRequest->id;

            $requestToSign = $this->validateAndGetDraft();
            $signedContent = $this->signDataWithCipher($requestToSign);

            $eHealthResponseAsArray = new EHealthEmployeeRequest()->create($signedContent);

            if (isset($eHealthResponseAsArray['error'])) {

                throw new EHealthValidationException(
                    $eHealthResponseAsArray['error']['message'] ?? 'E-Health Validation Failed'
                );
            }

            $validatedData = $eHealthResponseAsArray;

            $this->updateLocalRecords($requestToSign, $validatedData);

            session()?->flash('success', __('employees.sign_success'));
            $this->resetSignatureFields();
            Log::info('Successfully signed and will redirect.');

            return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);

        } catch (Exception $e) {
            $this->handleGeneralException($e);

        } catch (Throwable $e) {
            Log::critical('A critical throwable was caught during the signing process.', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->dispatch('flashMessage', ['message' => __('errors.unexpected_error'), 'type' => 'error', 'persistent' => true]);
            $this->dispatch('close-signature-modal');
        }
    }

    // === SHARED HELPERS (Moved from Trait) ===
    // They are shared across all form components.

    /**
     * Updates local records with the response from the eHealth API.
     */
    protected function updateLocalRecords(EmployeeRequest $request, array $eHealthResponse, ?LegalEntity $legalEntity = null): void
    {
        $legalEntity ??= legalEntity();

        $uuid = $eHealthResponse['id'];

        $request->update(
            [
                'uuid' => $uuid,
                'legal_entity_uuid' => $legalEntity->uuid,
                'inserted_at' => Carbon::now(),
                'status' => RequestStatus::SIGNED,
                'division_id' => $request->division_id,
            ]
        );

        $request->revision->update(
            [
                'ehealth_response' => $eHealthResponse['ehealth_response'],
                'status' => RevisionStatus::SENT,
            ]
        );
    }

    /**
     * Prepares the nested data structure for a Revision from flat form data.
     */
    protected function mapRevisionData(array $flatData): array
    {
        $employeeChunk = Arr::only($flatData, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
        $partyChunk = Arr::only($flatData, ['last_name', 'first_name', 'second_name', 'gender', 'birth_date', 'tax_id', 'no_tax_id', 'email', 'working_experience', 'about_myself']);
        $documentsChunk = $flatData['documents'] ?? [];
        $phonesChunk = $flatData['phones'] ?? [];
        $doctorChunk = $flatData['doctor'] ?? [];

        return [
            'employee_request_data' => $employeeChunk,
            'party' => $partyChunk,
            'documents' => $documentsChunk,
            'phones' => $phonesChunk,
            'doctor' => $doctorChunk,
        ];
    }

    private function handleEHealthResponseException(EHealthResponseException $e): void
    {
        $this->dispatch('flashMessage', ['message' => $e->getMessage(), 'type' => 'error', 'persistent' => true]);
        Log::error(
            'EHealth response error: ' . $e->getMessage(),
            [
                'details' => $e->getDetails(),
                'trace' => $e->getTraceAsString(),
            ]
        );
    }

    /**
     * Encapsulates the logic for creating and saving a new revision for a request.
     */
    protected function saveRevisionForRequest(BaseEmployee $request, array $nestedData): void
    {
        $revision = new Revision();
        $revision->data = $nestedData;
        $revision->status = RevisionStatus::PENDING;
        $request->revision()->save($revision);
    }

    /**
     * This is the abstract method that concrete components must implement.
     * It contains the unique logic for creating or updating a draft based on the component's context.
     *
     * @return EmployeeRequest
     */
    abstract protected function handleDraftPersistence(): EmployeeRequest;

    /**
     * Gets the draft and validates it, including KEP-specific validation.
     */
    protected function validateAndGetDraft(): EmployeeRequest
    {
        // We use the property on the class, which was set by handleDraftPersistence()
        $requestToSign = $this->employeeRequest;

        if (is_null($requestToSign) || !is_null($requestToSign->uuid)) {
            throw new RuntimeException(__('forms.draft_not_found_or_already_signed'), 400);
        }

        $this->form->validate($this->form->rulesForKepOnly());

        return $requestToSign;
    }

    /**
     * Signs the data using SignatureService.
     */
    private function signDataWithCipher(EmployeeRequest $requestToSign): string
    {
        $requestToSign->loadMissing('revision');
        $nestedDataForRevision = $requestToSign->revision->data;
        $payloadToSign = EHealth::employeeRequest()->schemaCreate($nestedDataForRevision);

        return signatureService()->signData(
            $payloadToSign,
            $this->form->password,
            $this->form->knedp,
            $this->form->keyContainerUpload,
            Auth::user()->party->tax_id
        );
    }

    // === SHARED HELPERS & UI LOGIC (Moved from Trait) ===

    /**
     * Resets only the fields related to the digital signature form inputs.
     */
    public function resetSignatureFields(): void
    {
        $this->form->reset('keyContainerUpload', 'password', 'knedp');
    }

    /**
     * A computed property that determines if the "no tax ID" mode can be enabled.
     */
    #[Computed]
    public function canEnableNoTaxId(): bool
    {
        foreach ($this->form->documents as $document) {
            if (!empty($document['number']) && in_array($document['type'], ['PASSPORT', 'NATIONAL_ID', 'REFUGEE_CERTIFICATE'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handles the click event on the "no tax ID" checkbox.
     */
    public function toggleNoTaxId(): void
    {
        if ($this->canEnableNoTaxId) {
            $this->form->party['noTaxId'] = !$this->form->party['noTaxId'];
            $this->syncTaxIdFromDocument();
        } else {
            $this->dispatch('flashMessage', [
                'message' => __('forms.no_tax_id_document_required'),
                'type' => 'error',
                'persistent' => true
            ]);
            $this->dispatch('scroll-to-element', selector: '#section-documents');
            $this->dispatch('highlight-section', selector: '#section-documents');
        }
    }

    /**
     * Syncs the Tax ID field with the number from a suitable document.
     */
    public function syncTaxIdFromDocument(): void
    {
        if ($this->form->party['noTaxId'] === false) {
            return;
        }

        foreach ($this->form->documents as $document) {
            if (!empty($document['number']) && in_array($document['type'], ['PASSPORT', 'NATIONAL_ID', 'REFUGEE_CERTIFICATE'])) {
                $this->form->party['taxId'] = $document['number'];

                return;
            }
        }
    }

    /**
     * Handles ValidationException by dispatching events for user feedback and scrolling.
     */
    private function handleValidationException(ValidationException $e): void
    {
        $validator = $e->validator;
        $allErrorKeys = collect($validator->errors()->keys())->unique();

        // A map of translatable field sections.
        $sections = [
            'form.documents' => __('forms.document'),
            'form.doctor.educations' => __('forms.education'),
            'form.doctor.specialities' => __('forms.specialities'),
            'form.doctor.qualifications' => __('forms.qualifications'),
            'form.doctor.scienceDegree' => __('forms.science_degree'),
        ];

        // A map of translatable specific fields (with wildcards for nested arrays).
        $fieldTranslations = [
            'form.knedp' => __('forms.provider'),
            'form.password' => __('forms.password'),
            'form.keyContainerUpload' => __('forms.key_file'),
            'form.party.firstName' => __('forms.first_name'),
            'form.party.lastName' => __('forms.last_name'),
            'form.party.secondName' => __('forms.second_name'),
            'form.party.gender' => __('forms.gender'),
            'form.party.birthDate' => __('forms.birth_date'),
            'form.party.taxId' => __('forms.tax_id'),
            'form.party.noTaxId' => __('forms.no_tax_id'),
            'form.party.email' => __('forms.email'),
            'form.party.workingExperience' => __('forms.working_experience'),
            'form.party.aboutMyself' => __('forms.about_myself'),
            'form.position' => __('forms.position'),
            'form.employeeType' => __('forms.role'),
            'form.startDate' => __('forms.start_date_work'),
            'form.endDate' => __('forms.end_date_work'),
            'form.party.phones.*.number' => __('forms.phone_number'),
            'form.party.phones.*.type' => __('forms.phone_type'),
            'form.documents.*.type' => __('forms.document_type'),
            'form.documents.*.number' => __('forms.document_number'),
            'form.documents.*.issuedBy' => __('forms.issued_by'),
            'form.documents.*.issuedAt' => __('forms.issued_at'),
            'form.doctor.educations.*.city' => __('forms.city'),
            'form.doctor.educations.*.institutionName' => __('forms.institution_name'),
            'form.doctor.educations.*.speciality' => __('forms.speciality'),
            'form.doctor.educations.*.degree' => __('forms.degree'),
            'form.doctor.educations.*.issuedDate' => __('forms.issued_date'),
            'form.doctor.educations.*.diplomaNumber' => __('forms.diploma_number'),
            'form.doctor.specialities.*.attestationName' => __('forms.attestationName'),
            'form.doctor.specialities.*.level' => __('forms.select_level'),
            'form.doctor.qualifications.*.institutionName' => __('forms.institutionName'),
            'form.doctor.qualifications.*.speciality' => __('forms.speciality'),

            'form.doctor.scienceDegree.city' => __('forms.city'),
            'form.doctor.scienceDegree.institutionName' => __('forms.institutionName'),
            'form.doctor.scienceDegree.speciality' => __('forms.speciality'),
            'form.doctor.scienceDegree.issuedDate' => __('forms.issuedDate'),
        ];

        $fieldsToDisplay = $allErrorKeys
            ->map(function ($key) use ($fieldTranslations, $sections, $allErrorKeys) {
                // Check if this is a top-level section key (e.g., 'form.documents')
                if (array_key_exists($key, $sections)) {
                    // Check if there are any more specific errors within this section.
                    $hasSpecificErrors = $allErrorKeys->contains(
                        fn ($errorKey) =>
                        str_starts_with($errorKey, $key . '.')
                    );

                    // If the section is a top-level error and has no specific sub-errors, it means the whole section is empty/missing.
                    if (!$hasSpecificErrors) {
                        return __('forms.section_not_filled', ['section' => $sections[$key]]);
                    }
                }

                // Check for an exact field translation match.
                if (isset($fieldTranslations[$key])) {
                    return $fieldTranslations[$key];
                }

                // Match nested keys with wildcards using regex (most reliable method).
                foreach ($fieldTranslations as $pattern => $translation) {
                    $patternRegex = '/^' . str_replace('\*', '\d+', preg_quote($pattern, '/')) . '$/';
                    if (preg_match($patternRegex, $key)) {
                        return $translation;
                    }
                }

                // Fallback to the key itself if no translation is found.
                return $key;
            })
            ->filter()
            ->unique()
            ->implode(', ');

        // Check if the flash message is empty and add a default message.
        if (empty($fieldsToDisplay)) {
            $flashMessage = __('forms.validation_error_unknown');
        } else {
            $flashMessage = __('forms.validation_fix_fields', ['fields' => $fieldsToDisplay]);
        }

        $this->dispatch('flashMessage', ['message' => $flashMessage, 'type' => 'error', 'persistent' => true]);

        if (!empty($validator->errors()->keys())) {
            $this->dispatch('validation-failed-scroll', firstErrorKey: $validator->errors()->keys()[0]);
        }
    }

    private function handleConnectionException(ConnectionException $e): void
    {
        $this->dispatch('flashMessage', ['message' => __('errors.ehealth_connection_error'), 'type' => 'error', 'persistent' => true]);
        Log::error('EHealth connection error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
    }

    /**
     * A centralized exception handler for generic, non-validation errors.
     */
    protected function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $this->dispatch('flashMessage', ['message' => $e->getMessage(), 'type' => 'error', 'persistent' => true]);
    }

    /**
     * Handles a detailed validation error from the eHealth API.
     */
    protected function handleEHealthValidationError(EHealthValidationException $e): void
    {
        $fullMessage = $e->getTranslatedMessage();
        $this->dispatch('flashMessage', ['message' => $fullMessage, 'type' => 'error', 'persistent' => true]);

        Log::error(
            'EHealth Validation Error: ' . $fullMessage,
            [
                'details' => $e->getDetails(),
                'trace' => $e->getTraceAsString(),
            ]
        );
    }

    private function removeEmptyValuesRecursively(array $array): array
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = $this->removeEmptyValuesRecursively($value);
            }
        }

        return array_filter($array, function ($value) {
            return !is_null($value) && $value !== '' && $value !== [];
        });
    }

    /**
     * A new centralized exception handler for various specific exceptions.
     */
    private function handleGeneralException(Exception $e): void
    {
        match (true) {
            $e instanceof ValidationException => $this->handleValidationException($e),
            $e instanceof EHealthValidationException => $this->handleEHealthValidationError($e),
            $e instanceof EHealthResponseException => $this->handleEHealthResponseException($e),
            $e instanceof ConnectionException => $this->handleConnectionException($e),
            default => $this->handleException($e),
        };
        $this->dispatch('close-signature-modal');
    }
}
