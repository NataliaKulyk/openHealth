<?php

namespace App\Livewire\Employee\Traits;

use App\Core\Arr;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Revision;
use App\Rules\TwoLettersSixDigits;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use App\Repositories\Repository;

trait ManagesEmployeeForm
{
    use WithFileUploads;

    protected ?Employee $employee = null;

    #[Locked]
    public ?int $employeeId = null;

    public ?EmployeeRequest $employeeRequest = null;
    public bool $showSignatureBlock = false;
    public bool $lockPartyFields = false;
    public bool $showSignatureModal = false;

    /**
     * REFACTORED: Loads form data from a finalized Employee model.
     * This is used when editing an existing, signed employee.
     *
     * @return void
     */
    public function loadEmployeeFromModel(): void
    {
        if ($this->employeeId) {
            // If the employee object isn't already loaded, find it.
            if (!$this->employee) {
                $this->employee = Employee::findOrFail($this->employeeId);
            }
            $this->form->populateFromModel($this->employee);
        }
    }

    /**
     * REFACTORED: Loads form data from a pending EmployeeRequest (a draft).
     * This is used when editing a draft that has not been signed yet.
     *
     * @return void
     */
    public function loadEmployeeFromRequest(): void
    {
        if ($this->employeeRequest) {
            // Set the state anchor so we don't lose the draft ID on re-renders
            $this->employeeRequestId = $this->employeeRequest->id;
            $this->form->populateFromRequest($this->employeeRequest);
        }
    }

    public function addPhone(): void
    {
        $this->form->party['phones'][] = ['type' => 'MOBILE', 'number' => ''];
    }

    public function removePhone(int $index): void
    {
        if (isset($this->form->party['phones'][$index])) {
            unset($this->form->party['phones'][$index]);
            $this->form->party['phones'] = array_values($this->form->party['phones']);
        }
    }


    /**
     * It correctly handles creating a new request or updating an existing draft.
     */
    public function save(): void
    {
        if (isset($this->form->party['phones'])) {
            $cleanedPhones = [];
            foreach ($this->form->party['phones'] as $phone) {
                if (isset($phone['number']) && is_string($phone['number'])) {
                    $digits = preg_replace('/[^0-9]/', '', $phone['number']);
                    $phone['number'] = !empty($digits) ? '+' . $digits : '';
                }
                $cleanedPhones[] = $phone;
            }
            $this->form->party['phones'] = $cleanedPhones;
        }

        try {
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();

            if ($this->employeeRequest) {
                // SCENARIO: Re-saving a PENDING request.
                DB::transaction(function () use ($preparedDataForDb) {

                    $requestAttributes = Arr::only($preparedDataForDb, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
                    $this->employeeRequest->fill($requestAttributes)->save();
                    if ($this->employeeRequest->party) {
                        $partyAttributes = Arr::only($preparedDataForDb, ['last_name', 'first_name', 'second_name', 'gender', 'birth_date', 'tax_id', 'no_tax_id', 'email', 'working_experience', 'about_myself']);
                        $this->employeeRequest->party->update($partyAttributes);
                    }
                    $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
                    if ($this->employeeRequest->revision) {
                        $this->employeeRequest->revision->update(['data' => $nestedDataForRevision]);
                    }
                });
            } else {
                // SCENARIO: Creating a NEW request for the first time.
                // This logic correctly uses the repository's store method.
                $this->employeeRequest = Repository::employee()->store(
                    $preparedDataForDb,
                    legalEntity(),
                    new EmployeeRequest(),
                    null,
                    true
                );
                $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
                $this->saveRevisionForRequest($nestedDataForRevision);
            }
            if ($this->employeeRequest) {
                $this->employeeRequestId = $this->employeeRequest->id;
            }

            session()->flash('success', __('forms.employee_request_saved_successfully'));

        } catch (ValidationException $e) {
            $this->dispatch('employee-form-failed');
            session()->flash('error-modal', __('forms.validation_failed_check_form'));
            throw $e;
        } catch (\Exception $e) {
            $this->handleException($e);
            throw $e;
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
     */
    private function saveRevisionForRequest(array $nestedData): void
    {
        if ($this->employeeRequest) {
            $revision = new Revision();
            $revision->data = $nestedData;
            $revision->status = Revision::STATUS_PENDING;
            $this->employeeRequest->revision()->save($revision);
        }
    }

    /**
     * NEW METHOD: This is the single entry point for the final "Sign" button in the modal.
     * It validates everything, saves, signs, and sends.
     */
    public function sign()
    {
        try {
            // STATE RESTORATION: Ensure we are working with the correct request.
            if ($this->employeeRequestId && !$this->employeeRequest) {
                $this->employeeRequest = EmployeeRequest::find($this->employeeRequestId);
            }

            // Step 1: Validate KEP fields first.
            $this->form->validate($this->form->rulesForKepOnly());

            // Step 2: Call the robust save() method to persist latest changes.
            $this->save();

            if (!$this->employeeRequest) {
                throw new Exception('Employee request could not be saved or found before signing.');
            }

            $this->employeeRequest->refresh();

            // Step 3: Prepare data for eHealth.
            $dataForSigning = Repository::employee()->formatEHealthRequest($this->employeeRequest->revision->data);
            // Step 4: Sign the data.
            $signedContent = signatureService()->signData(
                $dataForSigning,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $this->form->party['taxId']
            );

            // Step 5: Send to eHealth and redirect on success.
            if ($this->sendSignedContentToEhealth($signedContent)) {
                return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
            }

        } catch (Exception $e) {
            // Use the modal flash for user-friendly errors.
            session()->flash('error-modal', $e->getMessage());
            $this->handleException($e); // This will log the full error for developers.
        }
    }

    public function openSignatureModal(): void
    {
        try {
            $this->save();
            if ($this->employeeRequest) {
                $this->showSignatureModal = true;
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function closeSignatureModal()
    {
        $this->showSignatureModal = false;


        if ($this->employeeRequest && $this->employeeRequest->id) {
            return redirect()->route('employee.edit',
                                     ['employeeId' => $this->employeeRequest->id, 'legalEntity' => legalEntity()->id]);
        }
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
                session()->flash('success', __('forms.requestSignedAndSentToEHealth'));
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
     * A new helper method to run complex, cross-field validation.
     *
     * @throws ValidationException
     */
    protected function runCustomValidation(): void
    {
        $formData = $this->form->all();

        $validator = Validator::make($formData, [
            'documents.*.number' => [
                function($attribute, $value, $fail) use ($formData) {
                    if (data_get($formData, 'party.noTaxId', false)) {
                        $passportIndex = collect($formData['documents'])->search(fn($doc) => $doc['type'] === 'PASSPORT'
                        );

                        if ($passportIndex === false) {
                            $fail(__('validation.custom.passport_required_if_no_tax_id'));
                            return;
                        }

                        if ("documents.{$passportIndex}.number" === $attribute) {
                            $rule = new TwoLettersSixDigits();
                            $rule->validate($attribute, $value, $fail);
                        }
                    }
                }
            ],
        ]);
        $validator->validate();
    }

    private function handleException(Exception $e): void
    {
        dd($e->getMessage());
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $message = $e instanceof ValidationException
            ? __('forms.validation_failed_check_form')
            : __('forms.failed_to_save_employee_unexpected_error');
        session()->flash('error', $message);
        $this->dispatch('employee-form-failed');
    }

    protected function dispatchErrorMessage(array $errors, string $prefix = ''): void
    {
        $errorMessage = collect($errors)->flatten()->implode(', ');
        session()->flash('error', $prefix . $errorMessage);
        $this->dispatch('employee-form-failed');
    }
}
