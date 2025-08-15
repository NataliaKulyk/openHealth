<?php

declare(strict_types=1);

namespace App\Livewire\Employee\Traits;

use App\Classes\eHealth\Payloads\EHealthEmployeePayload;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Models\Employee\BaseEmployee;
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
     * A self-contained SIGN method. It controls the entire process of updating,
     * signing, and finalizing a draft within a single database transaction.
     * It does NOT call the generic save() method to avoid logical conflicts.
     *
     * @throws Exception
     */
    public function sign()
    {
        // Start a database transaction to ensure atomicity.
        DB::beginTransaction();

        try {
            // Step 1: Validate the form data first.
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();

            // Step 2: Forcefully load the draft we are working on.
            $requestToSign = EmployeeRequest::with('revision')->find($this->employeeRequestId);
            if (!$requestToSign) {
                throw new \RuntimeException('Employee Request draft not found for signing.');
            }
            if ($requestToSign->uuid) {
                throw new \RuntimeException('This request has already been signed and sent to eHealth.');
            }

            // Step 3: Update the draft and its revision with the latest data from the form.
            $requestAttributes = Arr::only($preparedDataForDb, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
            $requestToSign->fill($requestAttributes)->save();

            $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
            if ($requestToSign->revision) {
                $requestToSign->revision->update(['data' => $nestedDataForRevision]);
            } else {
                $this->saveRevisionForRequest($requestToSign, $nestedDataForRevision);
            }

            // Step 4: Prepare the data payload for eHealth using the updated revision data.
            // We call the dedicated Payload class for this.
            $payloadToSign = EHealthEmployeePayload::prepare($nestedDataForRevision);

            // Step 5: Sign the prepared payload.
            $signedContent = signatureService()->signData(
                $payloadToSign,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $nestedDataForRevision['party']['tax_id']
            );

            // Step 6: Send the signed request to eHealth via our API class.
            $ehealthData = new EHealthEmployeeRequest()->create($signedContent);

            // Step 7: Finalize local records with data from the eHealth response.
            $validatedData = new EHealthEmployeeRequest()->validateCreateResponseFromArray($ehealthData);

            $requestToSign->update(
                [
                    'uuid'   => $validatedData['uuid'],
                    'status' => RequestStatus::SIGNED,
                ]
            );

            $requestToSign->revision->update(
                [
                    'ehealth_response' => $ehealthData,
                    'status'           => RevisionStatus::SENT,
                ]
            );

            // If everything is successful, commit the transaction.
            DB::commit();

            // Step 8: Provide feedback to the user and redirect.
            session()?->flash('success', 'Request has been successfully signed and sent to eHealth.');
            $this->resetSignatureFields();
            return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);

        } catch (Exception $e) {
            // If anything fails, roll back the entire transaction.
            DB::rollBack();
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
