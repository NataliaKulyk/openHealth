<?php

declare(strict_types=1);

namespace App\Livewire\Employee\Traits;

use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Models\Employee\BaseEmployee;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Classes\eHealth\Api\EmployeeRequest as EHealthEmployeeRequest;
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
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();

            $this->employeeRequest = $this->getEmployeeRequestForSave();

            if ($this->employeeRequest && is_null($this->employeeRequest->uuid)) {
                DB::transaction(fn() => $this->updateExistingDraft($preparedDataForDb));
            } else {
                DB::transaction(fn() => $this->createNewDraft($preparedDataForDb));
            }

            $this->dispatch('flashMessage', ['message' => __('forms.employee_request_saved_successfully'), 'type' => 'success']);

        } catch (Exception $e) {
            $this->handleException($e);
            throw $e;
        }
    }

    /**
     * Handles the entire process of signing and sending the employee request to the eHealth API.
     * This method is self-contained and does not use the generic save() method to avoid logical conflicts.
     * It updates the draft with form data, signs it, sends it, and processes the response within a database
     * transaction.
     *
     * @throws Exception
     */
    public function sign()
    {
        // Note: The original 'sign' method logic provided by the user is preserved here.
        // For a more robust implementation, consider the self-contained refactored version
        // from the previous conversation to avoid calling the dual-purpose save() method.
        $this->save();

        try {
            if (!$this->employeeRequest) {
                $this->employeeRequest = \App\Models\Employee\EmployeeRequest::find($this->employeeRequestId);
                if (!$this->employeeRequest) {
                    throw new \RuntimeException('Employee request not found before signing.');
                }
            }

            // --- STAGE 1: DATA PREPARATION & NORMALIZATION ---
            $formattedPayload = EHealthEmployeeRequest::formatEHealthPayload($this->employeeRequest->revision->data);
            $normalizedPayload = schemaService()
                ->setDataSchema($formattedPayload, app(EHealthEmployeeRequest::class))
                ->requestSchemaNormalize()
                ->getNormalizedData();

            // --- STAGE 2: SIGNING ---
            $signedContent = signatureService()->signData(
                $normalizedPayload,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $this->employeeRequest->revision->data['party']['tax_id']
            );

            // --- STAGE 3: SENDING TO E-HEALTH ---
            // Call the static method that encapsulates sending and response handling.
            // It will return an array on success or throw an exception on failure.
            $ehealthData = EHealthEmployeeRequest::createFromSignedContent($signedContent);

            // --- STAGE 4: UPDATING LOCAL MODELS ---
            $validatedData = (new EHealthEmployeeRequest)->validateCreateResponseFromArray($ehealthData);

            // 4.1. Update the main EmployeeRequest model
            $this->employeeRequest->update(
                [
                    'uuid'   => $validatedData['uuid'],
                    'status' => RequestStatus::SIGNED,
                ]
            );

            // 4.2. Update the revision with the full eHealth response and final status
            $this->employeeRequest->revision->update(
                [
                    'ehealth_response' => $ehealthData,
                    'status'           => RevisionStatus::SENT,
                ]
            );

            // --- STAGE 5: FINALIZE ---
            session()?->flash('success', 'Request has been successfully signed and sent to eHealth.');
            $this->resetSignatureFields();
            return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);

        } catch (\Exception $e) {
            // A single catch block for all exceptions, including from eHealth.
            session()?->flash('error-modal', $e->getMessage());
            $this->handleException($e);
            return null;
        }
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
     *
     * @throws Exception
     */
    protected function createNewDraft(array $preparedDataForDb): void
    {
        $newRequest = Repository::employee()->store(
            $preparedDataForDb,
            legalEntity(),
            new EmployeeRequest(),
            null,
            true
        );

        $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

        $this->employeeRequest = $newRequest;
        if (property_exists($this, 'employeeRequestId')) {
            $this->employeeRequestId = $newRequest->id;
        }
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
     * Resets only the fields related to the digital signature form inputs.
     */
    public function resetSignatureFields(): void
    {
        $this->form->reset('keyContainerUpload', 'password', 'knedp');
    }

    /**
     * A centralized exception handler for the trait.
     */
    private function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        $message = $e instanceof ValidationException
            ? __('forms.validation_failed_check_form')
            : $e->getMessage();

        $this->dispatch('flashMessage', [
            'message' => $message,
            'type' => 'error',
        ]);
    }
}
