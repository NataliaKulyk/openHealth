<?php

namespace App\Livewire\Employee\Traits;

use App\Core\Arr;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Revision;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use App\Repositories\Repository;
use Illuminate\Validation\ValidationException;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;

trait ManagesEmployeeForm
{
    use WithFileUploads;

    protected ?Employee $employee = null;
    public ?EmployeeRequest $employeeRequest = null;
    public ?int $employeeRequestId = null;

    /**
     * The main save method.
     */
    public function save(): void
    {
        try {
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();

            if (property_exists($this, 'employeeRequestId') && $this->employeeRequestId) {
                $this->employeeRequest = EmployeeRequest::find($this->employeeRequestId);
            }

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
     * THE FIX: This method no longer updates the Party model directly.
     * It only updates the EmployeeRequest and saves all changes to the Revision.
     */
    protected function updateExistingDraft(array $preparedDataForDb): void
    {
        // 1. Update the main request attributes (position, start_date, etc.)
        $requestAttributes = Arr::only($preparedDataForDb, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
        $this->employeeRequest->fill($requestAttributes)->save();

        // 2. We DO NOT update the associated party.
        // Instead, we prepare all data (including potentially changed party data) for the revision.
        $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);

        // 3. Update or create the revision with the complete form data.
        if ($this->employeeRequest->revision) {
            $this->employeeRequest->revision->update(['data' => $nestedDataForRevision]);
        } else {
            $this->saveRevisionForRequest($this->employeeRequest, $nestedDataForRevision);
        }
    }

    /**
     * Helper method to create a new draft and its relations.
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
     * Helper method to prepare the nested data structure required for a Revision.
     *
     * @param array $flatData The flat data array from the form.
     * @return array The nested data array.
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
     * Helper to encapsulate saving the revision.
     * Now accepts the EmployeeRequest model to be more explicit.
     */
    private function saveRevisionForRequest(EmployeeRequest $request, array $nestedData): void
    {
        $revision = new Revision();
        $revision->data = $nestedData;
        $revision->status = Revision::STATUS_PENDING;
        // Save the revision specifically for the provided request.
        $request->revision()->save($revision);
    }

    /**
     * Resets only the fields related to the digital signature.
     * This should be called after a signing attempt (successful or not).
     */
    public function resetSignatureFields(): void
    {
        $this->form->reset('keyContainerUpload', 'password', 'knedp');
    }

    /**
     * It validates everything, saves, signs, and sends.
     */
    public function sign()
    {
        $this->save();

        // Now, we attempt to sign and send.
        try {
            if ($this->employeeRequestId && !$this->employeeRequest) {
                $this->employeeRequest = EmployeeRequest::find($this->employeeRequestId);
            }

            $this->form->validate($this->form->rulesForKepOnly());

            if (!$this->employeeRequest) {
                throw new \RuntimeException('Employee request could not be saved or found before signing.');
            }

            $dataForSigning = $this->formatEHealthRequest($this->employeeRequest->revision->data);

            $signedContent = signatureService()->signData(
                $dataForSigning,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $this->form->party['taxId']
            );

            if ($this->sendSignedContentToEhealth($signedContent)) {
                session()->flash('success', __('forms.request_signed_and_sent_to_eHealth'));

                // On full success, we clear the signature fields and redirect.
                $this->resetSignatureFields();
                return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
            }
            // If sendSignedContentToEhealth returns false, it means it already set a flash error.
            // We do NOT reset fields here, allowing the user to try again.

        } catch (ValidationException $e) {
            // This handles validation errors for the signature fields.
            // We do NOT reset fields, so the user can correct their input.
            $this->dispatch('employee-form-failed');
            session()->flash('error-modal', __('forms.validation_failed_check_form'));
            // We re-throw to let Livewire handle the validation feedback.
            throw $e;
        } catch (Exception $e) {
            session()->flash('error-modal', $e->getMessage());
            $this->handleException($e);
        }
    }

    /**
     * REFACTORED: Now uses the form's unpackRevisionData helper.
     */
    private function formatEHealthRequest(array $revisionData): array
    {
        $employeeData = $revisionData['employee_request_data'];
        $partyData = $revisionData['party'];
        $documentsData = $revisionData['documents'];
        $phonesData = $revisionData['phones'];
        $doctorData = $revisionData['doctor'];

        $apiEmployeeRequest = [
            'position' => $employeeData['position'] ?? null,
            'status' => 'NEW',
            'employee_type' => $employeeData['employee_type'] ?? null,
            'legal_entity_id' => (string)($employeeData['legal_entity_id'] ?? legalEntity()->id),
            'start_date' => isset($employeeData['start_date']) ? Carbon::parse($employeeData['start_date'])->format('Y-m-d') : null,
        ];

        if (!empty($employeeData['end_date'])) {
            $apiEmployeeRequest['end_date'] = Carbon::parse($employeeData['end_date'])->format('Y-m-d');
        }

        $apiEmployeeRequest['party'] = [
            'first_name' => $partyData['first_name'] ?? null,
            'last_name' => $partyData['last_name'] ?? null,
            'second_name' => $partyData['second_name'] ?? null,
            'birth_date' => isset($partyData['birth_date']) ? Carbon::parse($partyData['birth_date'])->format('Y-m-d') : null,
            'gender' => $partyData['gender'] ?? null,
            'no_tax_id' => (bool)($partyData['no_tax_id'] ?? false),
            'tax_id' => $partyData['tax_id'] ?? null,
            'email' => $partyData['email'] ?? null,
            'working_experience' => isset($partyData['working_experience']) ? (int)$partyData['working_experience'] : null,
            'about_myself' => $partyData['about_myself'] ?? null,

            'phones' => array_map(
                fn($phone) => ['type' => $phone['type'], 'number' => $phone['number']],
                $phonesData
            ),

            'documents' => array_map(
                fn($doc) => [
                    'type' => $doc['type'],
                    'number' => $doc['number'],
                    'issued_by' => $doc['issued_by'] ?? null,
                    'issued_at' => isset($doc['issued_at']) && !empty($doc['issued_at']) ? Carbon::parse($doc['issued_at'])->format('Y-m-d') : null
                ],
                $documentsData
            ),
        ];

        if (($employeeData['employee_type'] ?? null) === 'DOCTOR' && !empty($doctorData)) {
            $doctorPayload = [];
            if (!empty($doctorData['educations'])) $doctorPayload['educations'] = $doctorData['educations'];
            if (!empty($doctorData['qualifications'])) $doctorPayload['qualifications'] = $doctorData['qualifications'];
            if (!empty($doctorData['specialities'])) $doctorPayload['specialities'] = $doctorData['specialities'];
            if (!empty($doctorData['science_degrees'])) $doctorPayload['science_degree'] = $doctorData['science_degrees'][0];

            if (!empty($doctorPayload)) $apiEmployeeRequest['doctor'] = $doctorPayload;
        }

        return ['employee_request' => $apiEmployeeRequest];
    }

    /**
     * Sends the signed content to eHealth API and returns success status.
     */
    protected function sendSignedContentToEhealth(string $signedContent): bool
    {
        try {
            $ehealthResponse = EmployeeRequestApi::createEmployeeRequest(
                [
                    'signed_content' => $signedContent,
                    'signed_content_encoding' => 'base64',
                ]
            );

            if (isset($ehealthResponse['id'])) {
                $this->employeeRequest->uuid = $ehealthResponse['id'];
                $this->employeeRequest->inserted_at = $ehealthResponse['inserted_at'];
                $this->employeeRequest->legal_entity_uuid = $ehealthResponse['legal_entity_id'];
                $this->employeeRequest->updated_at = $ehealthResponse['updated_at'];
                $this->employeeRequest->save();

                $this->dispatch('signature-successful');
                return true;
            } else {
                $errorMessage = $ehealthResponse['error']['message'] ?? __('forms.failed_to_send_request_to_esoz_unknown_error');
                if (isset($ehealthResponse['error']['invalid']) && is_array($ehealthResponse['error']['invalid'])) {
                    $detailedErrors = collect($ehealthResponse['error']['invalid'])->map(fn($error) => $error['description'] ?? $error['rule'] ?? __('forms.details_unknown'))->implode('; ');
                    $errorMessage .= ' ' . $detailedErrors;
                }
                session()->flash('error', $errorMessage);
                $this->dispatch('employee-form-failed');
                return false;
            }
        } catch (Exception $e) {
            $this->handleException($e);
            return false;
        }
    }

    /**
     * Helper method for dispatching flash messages.
     * I've renamed it for clarity.
     */
    private function dispatchFlashMessage(string $message, string $type = 'success', array $errors = []): void
    {
        $this->dispatch('flashMessage', [
            'message' => $message,
            'type'    => $type,
            'errors'  => $errors
        ]);
    }

    /**
     * Centralized exception handler.
     */
    private function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        $message = $e instanceof ValidationException
            ? __('forms.validation_failed_check_form')
            : __('forms.failed_to_save_employee_unexpected_error');

        $this->dispatchFlashMessage($message, 'error');
    }
}
