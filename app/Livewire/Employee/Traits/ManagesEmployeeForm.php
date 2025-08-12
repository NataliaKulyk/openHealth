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
            throw $e;
        }
    }

    /**
     * A self-contained SIGN method. It controls the entire process of validating,
     * saving, signing, and finalizing a draft within a single database transaction.
     */
    public function sign()
    {
        DB::beginTransaction();
        try {
            // Step 1: Use the unified method to validate and save/update the draft.
            $requestToSign = $this->saveOrUpdateDraft();

            // Step 2: Validate KEP-specific fields.
            $this->form->validate($this->form->rulesForKepOnly());

            // Step 3: Proceed with signing.
            $requestToSign->load('revision');
            $nestedDataForRevision = $requestToSign->revision->data;

            $payloadToSign = EHealthEmployeePayload::prepare($nestedDataForRevision);

            $signedContent = signatureService()->signData(
                $payloadToSign,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $nestedDataForRevision['party']['tax_id']
            );

            // Step 4: Send to eHealth API.
            $ehealthData = new EHealthEmployeeRequest()->create($signedContent);
            $validatedData = new EHealthEmployeeRequest()->validateCreateResponseFromArray($ehealthData);

            // Step 5: Update local records with eHealth response.
            $requestToSign->update(['uuid' => $validatedData['uuid'], 'status' => RequestStatus::SIGNED]);
            $requestToSign->revision->update(['ehealth_response' => $ehealthData, 'status' => RevisionStatus::SENT]);

            DB::commit();

            session()?->flash('success', __('employees.sign_success'));
            $this->resetSignatureFields();
            return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);

        } catch (EHealthValidationException $e) {
            DB::rollBack();
            $this->handleEHealthValidationError($e);
        } catch (EHealthResponseException $e) {
            DB::rollBack();
            $this->handleException($e);
        } catch (ValidationException $e) {
            DB::rollBack();
            $this->handleValidationException($e);
        } catch (Exception $e) {
            DB::rollBack();
            // Special handling for signature-related errors (e.g., wrong password)
            if (str_contains(strtolower($e->getMessage()), 'password') || str_contains(strtolower($e->getMessage()), 'key')) {
                // Add an error specifically to the password field to show it inside the modal.
                $this->addError('form.password', $e->getMessage());
            } else {
                // For all other unexpected errors, use the general handler.
                $this->handleException($e);
            }
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

        $this->dispatch('close-signature-modal');
        $this->dispatch('flashMessage', ['message' => $fullMessage, 'type' => 'error', 'persistent' => true]);
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
}
