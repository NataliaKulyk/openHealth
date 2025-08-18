<?php

declare(strict_types=1);

namespace App\Livewire\Employee\Traits;

use App\Classes\eHealth\Api\EmployeeRequest as EHealthEmployeeRequest;
use App\Classes\eHealth\Payloads\EHealthEmployeePayload;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Exceptions\CustomValidationException;
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
            session()->flash('success', __('forms.employee_request_saved_successfully'));
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

            session()->flash('success', __('forms.employee_request_saved_successfully'));
            $this->dispatch('open-signature-modal');

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * The main SIGN method, now simplified and decomposed.
     */
    public function sign()
    {
        DB::beginTransaction();

        try {
            // Step 1: Get the draft and perform local validation
            $requestToSign = $this->validateAndGetDraft();

            // Step 2: Sign the data with Cipher API
            $signedContent = $this->signDataWithCipher($requestToSign);

            // Step 3: Send the signed data to eHealth API
            $ehealthResponseData = $this->sendToEHealth($signedContent);

            // Step 4: Update local records
            $this->updateLocalRecords($requestToSign, $ehealthResponseData);

            DB::commit();

            session()->flash('success', __('employees.sign_success'));
            $this->resetSignatureFields();
            $this->dispatch('close-signature-modal');
            return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
        } catch (CustomValidationException $e) {
            DB::rollBack();
            $this->addError('form.password', $e->getMessage());
        } catch (ValidationException $e) {
            DB::rollBack();
            $this->handleValidationException($e);
        } catch (EHealthValidationException $e) {
            DB::rollBack();
            $this->handleEHealthValidationError($e);
        } catch (EHealthResponseException $e) {
            DB::rollBack();
            session()->flash('error', $e->getMessage());
            Log::error('EHealth response error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        } catch (Exception $e) {
            DB::rollBack();
            session()->flash('error', __('forms.an_unexpected_error_occurred'));
            Log::error('An unexpected error occurred during signing process: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        } finally {
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
    protected function handleEHealthValidationError(EHealthValidationException $e): void
    {
        $reverseKeyMap = EHealthEmployeePayload::getReverseKeyMap();

        $errorList = collect($e->getDetails())->map(function ($detail) use ($reverseKeyMap) {
            $param = $detail['entry'] ?? ($detail['param'] ?? 'unknown');
            $param = str_replace(['$.', 'employee_request.'], '', $param);

            $localKey = strtr($param, $reverseKeyMap);
            $fieldName = __('validation.attributes.' . $localKey);

            if ($fieldName === ('validation.attributes.' . $localKey)) {
                $fieldName = $localKey; // Fallback to technical name if translation not found
            }

            $message = $detail['rules'][0]['description'] ?? ($detail['msg'] ?? 'Invalid value');
            return "<b>{$fieldName}:</b> {$message}";
        });

        $header = __('forms.ehealth_validation_error_header');
        $fullMessage = "<p class='mb-2'>{$header}</p><ul class='list-disc list-inside text-left'>" .
            $errorList->map(fn($item) => "<li>{$item}</li>")->implode('') .
            "</ul>";

        session()->flash('error', $fullMessage);
    }

    /**
     * A centralized exception handler for generic, non-validation errors.
     */
    private function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $this->dispatch('close-signature-modal');
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
     * @throws ValidationException
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
                    __('forms.section_contains_errors') // Використовуємо єдиний ключ перекладу
                );
            }
        }

        $errors = $validator->errors()->keys();
        $firstErrorKey = $errors[0] ?? null;

        $isKepError = collect($errors)->contains(fn ($key) => str_starts_with($key, 'form.password') || str_starts_with($key, 'form.keyContainerUpload') || str_starts_with($key, 'form.knedp'));

        if ($isKepError) {
            throw $e;
        }

        $fieldNames = collect($errors)
            ->map(fn($key) => $validator->getDisplayableAttribute($key))
            ->unique()
            ->implode(', ');

        $flashMessage = __('forms.validation_fix_fields', ['fields' => $fieldNames]);

        $this->dispatch('close-signature-modal');
        $this->dispatch('flashMessage', ['message' => $flashMessage, 'type' => 'error', 'persistent' => true]);

        if ($firstErrorKey) {
            $this->dispatch('validation-failed-scroll', firstErrorKey: $firstErrorKey);
        }

        throw $e;
    }

    /**
     * Gets the draft and validates it, including KEP-specific validation.
     *
     * @throws ValidationException
     * @throws CustomValidationException
     */
    private function validateAndGetDraft(): EmployeeRequest
    {
        $requestToSign = $this->getEmployeeRequestForSave();

        // Handle case where draft is not found (shouldn't happen, but good practice)
        if (is_null($requestToSign) || !is_null($requestToSign->uuid)) {
            // We use a custom exception here for more specific handling
            throw new CustomValidationException(__('forms.draft_not_found_or_already_signed'), 'draft');
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
     * @throws Exception
     */
    private function signDataWithCipher(EmployeeRequest $requestToSign): string
    {
        $requestToSign->load('revision');
        $nestedDataForRevision = $requestToSign->revision->data;

        $payloadToSign = EHealthEmployeePayload::prepare($nestedDataForRevision);

        $signedContent = signatureService()->signData(
            $payloadToSign,
            $this->form->password,
            $this->form->knedp,
            $this->form->keyContainerUpload,
            $nestedDataForRevision['party']['tax_id']
        );

        if (empty($signedContent) || !is_string($signedContent)) {
            throw new Exception(__('employees.errors.signature_failed_unexpected'));
        }

        return $signedContent;
    }

    /**
     * Sends the signed content to eHealth API.
     *
     * @param string $signedContent
     * @return array
     * @throws EHealthResponseException
     * @throws EHealthValidationException
     */
    private function sendToEHealth(string $signedContent): array
    {
        $ehealthData = new EHealthEmployeeRequest()->create($signedContent);

        return new EHealthEmployeeRequest()->validateCreateResponseFromArray($ehealthData);
    }

    /**
     * Updates the local database records with the eHealth API response.
     *
     * @param EmployeeRequest $requestToSign
     * @param array $ehealthResponseData
     * @return void
     */
    private function updateLocalRecords(EmployeeRequest $requestToSign, array $ehealthResponseData): void
    {
        $requestToSign->update(
            [
                'uuid'   => $ehealthResponseData['uuid'],
                'status' => RequestStatus::SIGNED,
            ]
        );
        $requestToSign->revision->update(
            [
                'ehealth_response' => $ehealthResponseData,
                'status'           => RevisionStatus::SENT,
            ]
        );
    }
}
