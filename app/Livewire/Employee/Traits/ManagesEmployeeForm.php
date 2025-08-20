<?php

declare(strict_types=1);

namespace App\Livewire\Employee\Traits;

use App\Classes\eHealth\Api\EmployeeRequest as EHealthEmployeeRequest;
use App\Classes\eHealth\Payloads\EHealthEmployeePayload;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\Employee\BaseEmployee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Revision;
use App\Repositories\Repository;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use RuntimeException;

trait ManagesEmployeeForm
{
    use WithFileUploads;

    protected ?BaseEmployee $employeeRequest, $employee = null;

    /**
     * An abstract method to be implemented in the component,
     * which should find and return the existing draft request.
     */
    abstract protected function getEmployeeRequestForSave(): ?EmployeeRequest;

    /**
     * The main save method for creating or updating drafts without signing.
     */
    public function save(): void
    {
        try {
            DB::transaction(fn() => $this->saveOrUpdateDraft());
            $this->dispatch('flashMessage', ['message' => __('forms.employee_request_saved_successfully'), 'type' => 'success']);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Saves the form as a draft and then opens the signature modal.
     * This is triggered by the "Complete and Sign" button.
     */
    public function prepareForSigning(): void
    {
        try {
            DB::transaction(fn() => $this->saveOrUpdateDraft());
            $this->dispatch('flashMessage', ['message' => __('forms.employee_request_saved_successfully'), 'type' => 'success']);
            $this->dispatch('open-signature-modal');

        } catch (ValidationException $e) {
            $this->handleValidationException($e);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function sign()
    {
        Log::info('Attempting to sign.');

        try {
            // Step 1: Validate local form data and get the draft.
            $requestToSign = $this->validateAndGetDraft();

            // Step 2: Sign the data with the Cipher API.
            $signedContent = $this->signDataWithCipher($requestToSign);

            // Step 3: Send data to the eHealth API and update the local database.
            // If this step fails, it will throw an exception caught below.
            $ehealthResponseData = new \App\Classes\eHealth\Api\EmployeeRequest()->create($signedContent);
            $this->updateLocalRecords($requestToSign, $ehealthResponseData);

            // Final step: The successful path. This code is only reached if no exceptions are thrown.
            session()->flash('success', __('employees.sign_success'));
            $this->resetSignatureFields();
            Log::info('Successfully signed and will redirect.');

            return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
        } catch (ValidationException $e) {
            $this->handleValidationException($e);
            if (!$this->isKepValidationError($e)) {
                $this->dispatch('close-signature-modal');
            }
        } catch (EHealthValidationException $e) {
            $this->handleEHealthValidationError($e);
            $this->dispatch('close-signature-modal');
        } catch (EHealthResponseException $e) {
            $this->dispatch('flashMessage', ['message' => $e->getMessage(), 'type' => 'error', 'persistent' => true]);
            Log::error('EHealth response error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('close-signature-modal');
        } catch (\Exception $e) {
            $this->handleException($e);
            $this->dispatch('close-signature-modal');
        }
    }

    /**
     * Resets only the fields related to the digital signature form inputs.
     */
    public function resetSignatureFields(): void
    {
        $this->form->reset('keyContainerUpload', 'password', 'knedp');
    }

    /**
     * Updates an existing draft request and its revision with the latest form data.
     */
    protected function updateExistingDraft(array $preparedDataForDb): void
    {
        $requestAttributes = Arr::only($preparedDataForDb, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
        $this->employeeRequest->fill($requestAttributes)->save();
        $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
        if ($this->employeeRequest?->revision()) {
            $this->employeeRequest?->revision()->update(['data' => $nestedDataForRevision]);
        } else {
            $this->saveRevisionForRequest($this->employeeRequest, $nestedDataForRevision);
        }
    }

    /**
     * Creates a new draft request and its associated revision.
     */
    protected function createNewDraft(array $preparedDataForDb): void
    {
        $newRequest = Repository::employee()->store($preparedDataForDb, legalEntity(), new EmployeeRequest(), null, true);
        $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);
        $this->employeeRequest = $newRequest;
        if (property_exists($this, 'employeeRequestId')) {
            $this->employeeRequestId = $newRequest->id;
        }
    }

    /**
     * Handles a detailed validation error from the eHealth API.
     */
    /**
     * Handles a detailed validation error from the eHealth API.
     */
    protected function handleEHealthValidationError(\App\Exceptions\EHealth\EHealthValidationException $e): void
    {
        $reverseKeyMap = EHealthEmployeePayload::getReverseKeyMap();

        $errorList = collect($e->getDetails())->map(function ($detail) use ($reverseKeyMap) {
            $ehealthKey = $detail['entry'] ?? ($detail['param'] ?? 'unknown');
            $ehealthKey = str_replace(['$.', 'employee_request.'], '', $ehealthKey);

            $message = $detail['rules'][0]['description'] ?? ($detail['msg'] ?? 'Некоректне значення.');

            // Використовуємо оригінальний ключ як назву поля
            $fieldName = $ehealthKey;

            return "{$fieldName}: {$message}";
        })->implode("\n");

        $header = __('forms.ehealth_validation_error_header');
        $fullMessage = "{$header}\n{$errorList}";

        $this->dispatch('flashMessage', ['message' => $fullMessage, 'type' => 'error', 'persistent' => true]);
    }

    /**
     * A centralized exception handler for generic, non-validation errors.
     */
    private function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $this->dispatch('flashMessage', ['message' => $e->getMessage(), 'type' => 'error', 'persistent' => true]);
    }

    /**
     * Prepares the nested data structure required for a Revision from flat form data.
     */
    private function prepareDataForRevision(array $flatData): array
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

    /**
     * Encapsulates the logic for creating and saving a new revision for a request.
     */
    private function saveRevisionForRequest(BaseEmployee $request, array $nestedData): void
    {
        $revision = new Revision();
        $revision->data = $nestedData;
        $revision->status = RevisionStatus::PENDING;
        $request->revision()->save($revision);
    }

    /**
     * The single source of truth for creating or updating a draft.
     * This method contains the core logic for validation and persistence.
     *
     * @return EmployeeRequest The saved or updated request instance.
     * @throws ValidationException
     */
    private function saveOrUpdateDraft(): EmployeeRequest
    {
        $this->form->validate($this->form->rulesForSave());

        $preparedDataForDb = $this->form->getPreparedData();
        $this->employeeRequest = $this->getEmployeeRequestForSave();

        if ($this->employeeRequest && is_null($this->employeeRequest->uuid)) {
            $this->updateExistingDraft($preparedDataForDb);
        } else {
            $this->createNewDraft($preparedDataForDb);
        }

        return $this->employeeRequest;
    }

    /**
     * Handles ValidationException by dispatching events for user feedback and scrolling.
     *
     * @param ValidationException $e
     */
    private function handleValidationException(ValidationException $e): void
    {
        $validator = $e->validator;

        $errorKeys = collect($validator->errors()->keys());
        $sections = [
            'form.documents',
            'form.doctor.educations',
            'form.doctor.specialities',
            'form.doctor.qualifications',
            'form.doctor.scienceDegrees',
        ];

        foreach ($sections as $sectionPrefix) {
            if ($errorKeys->contains(fn($key) => str_starts_with($key, $sectionPrefix . '.'))) {
                $validator->errors()->add(
                    $sectionPrefix,
                    __('forms.section_contains_errors')
                );
            }
        }

        $errors = $validator->errors()->keys();
        $firstErrorKey = $errors[0] ?? null;

        $keyMap = [
            'position' => 'position',
            'employeeType' => 'employee_type',
            'startDate' => 'start_date',
            'endDate' => 'end_date',
            'divisionId' => 'select_division',
            'party.lastName' => 'last_name',
            'party.firstName' => 'first_name',
            'party.secondName' => 'second_name',
            'party.gender' => 'gender',
            'party.birthDate' => 'birth_date',
            'party.phones' => 'phones',
            'party.phones.0.number' => 'phone_number',
            'party.phones.*.number' => 'phone_number',
            'party.phones.*.type' => 'phone_type',
            'party.taxId' => 'tax_id',
            'party.noTaxId' => 'no_tax_id',
            'party.email' => 'email',
            'party.workingExperience' => 'working_experience',
            'party.aboutMyself' => 'about_myself',
            'documents.*.type' => 'document_type',
            'documents.*.number' => 'document_number',
            'documents.*.issuedBy' => 'issued_by',
            'documents.*.issuedAt' => 'issued_at',
            'doctor.educations.*.country' => 'country',
            'doctor.educations.*.city' => 'city',
            'doctor.educations.*.institutionName' => 'institution_name',
            'doctor.educations.*.issuedDate' => 'issued_date',
            'doctor.educations.*.diplomaNumber' => 'diploma_number',
            'doctor.educations.*.degree' => 'degree',
            'doctor.educations.*.speciality' => 'speciality',
        ];

        $fieldNames = collect($errors)
            ->map(function ($key) use ($keyMap, $validator) {
                $cleanKey = str_replace('form.', '', $key);
                $translationKey = $keyMap[$cleanKey] ?? null;

                if (!$translationKey) {
                    // Special handling for nested array keys like `documents.*.type`
                    foreach ($keyMap as $k => $v) {
                        if (str_contains($cleanKey, $k)) {
                            $translationKey = $v;
                            break;
                        }
                    }
                }

                $translated = $translationKey ? __('forms.' . $translationKey) : null;

                if ($translated && $translated !== 'forms.' . $translationKey) {
                    return $translated;
                }

                return $validator->getDisplayableAttribute($key);
            })
            ->unique()
            ->implode(', ');

        $flashMessage = __('forms.validation_fix_fields', ['fields' => $fieldNames]);

        $this->dispatch('flashMessage', ['message' => $flashMessage, 'type' => 'error', 'persistent' => true]);

        if ($firstErrorKey) {
            $this->dispatch('validation-failed-scroll', firstErrorKey: $firstErrorKey);
        }
    }

    /**
     * Gets the draft and validates it, including KEP-specific validation.
     *
     * @throws ValidationException
     * @throws RuntimeException
     */
    private function validateAndGetDraft(): EmployeeRequest
    {
        $requestToSign = $this->getEmployeeRequestForSave();

        // Handle case where draft is not found (shouldn't happen, but good practice)
        if (is_null($requestToSign) || !is_null($requestToSign->uuid)) {
            // We use a custom exception here for more specific handling
            throw new RuntimeException(__('forms.draft_not_found_or_already_signed'), 400);
        }

        // Validate KEP-specific fields.
        $this->form->validate($this->form->rulesForKepOnly());

        return $requestToSign;
    }

    /**
     * Signs the data using SignatureService.
     *
     * @param EmployeeRequest $requestToSign
     * @return string
     * @throws RuntimeException
     */
    private function signDataWithCipher(EmployeeRequest $requestToSign): string
    {
        $requestToSign->load('revision');
        $nestedDataForRevision = $requestToSign->revision->data;

        $payloadToSign = EHealthEmployeePayload::prepare($nestedDataForRevision);

        return signatureService()->signData(
            $payloadToSign,
            $this->form->password,
            $this->form->knedp,
            $this->form->keyContainerUpload,
            $nestedDataForRevision['party']['tax_id']
        );
    }

    /**
     * Step 4: Update local records.
     */
    private function updateLocalRecords(EmployeeRequest $request, array $ehealthResponseData): void
    {
        $uuid = $ehealthResponseData['id'];

        $request->update(
            [
                'uuid'   => $uuid,
                'status' => RequestStatus::SIGNED,
            ]
        );

        $request->revision->update(
            [
                'ehealth_response' => $ehealthResponseData['ehealth_response'],
                'status'           => RevisionStatus::SENT,
            ]
        );
    }

    /**
     * Checks if a ValidationException contains KEP-related errors.
     *
     * @param ValidationException $e
     * @return bool
     */
    private function isKepValidationError(ValidationException $e): bool
    {
        $errors = $e->validator->errors()->keys();
        return collect($errors)->contains(fn($key) =>
            str_contains($key, 'form.password') ||
            str_contains($key, 'form.keyContainerUpload') ||
            str_contains($key, 'form.knedp')
        );
    }
}
