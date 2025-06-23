<?php

namespace App\Livewire\Employee\Traits;

use App\Classes\Cipher\Api\CipherApi;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Rules\TwoLettersSixDigits;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use App\Repositories\Repository;

trait ManagesEmployeeForm
{
    use WithFileUploads;

    public bool $lockPartyFields = false;

    protected ?Employee $employee = null;

    #[Locked]
    public ?int $employeeId = null;

    public ?EmployeeRequest $employeeRequest = null;
    public bool $showSignatureBlock = false;

    /**
     * Load the employee model based on the locked employeeId.
     */
    public function loadEmployee(): void
    {
        if ($this->employeeId) {
            $this->employee = Employee::findOrFail($this->employeeId);
            $this->form->populateFromModel($this->employee);
        }
    }

    /**
     * Save or Update the employee data.
     */
    public function save(): void
    {
        try {
            $this->form->validate($this->form->rulesForSave());
            $this->runCustomValidation();
            $preparedDataForDb = $this->form->getPreparedData();
            $preparedDataForDb['legal_entity_uuid'] = legalEntity()->uuid;
            $preparedDataForDb['legal_entity_id'] = legalEntity()->id;

            if ($this->employeeRequest) {
                // Re-saving a pending request.
                if ($this->employeeRequest->revision) {
                    $this->employeeRequest->revision->update(['data' => $preparedDataForDb]);
                }
            } else {
                // Creating a new request.
                if ($this->employee) {
                    $this->employeeRequest = Repository::employee()->createChangeRequestForExistingEmployee(
                        $preparedDataForDb,
                        $this->employee->uuid,
                        legalEntity()
                    );
                } else {
                    $this->employeeRequest = Repository::employee()->store(
                        $preparedDataForDb,
                        legalEntity(),
                        new EmployeeRequest(),
                        null,
                        true
                    );
                }
            }
            session()->flash('success', __('forms.employee_request_saved_successfully'));
            $this->showSignatureBlock = true;
        } catch (Exception $e) {
            $this->handleException($e);
            if ($e instanceof ValidationException) throw $e;
        }
    }

    /**
     * Handles the signing process and returns a redirect on success.
     */
    public function sign()
    {
        try {
            $this->save();
            $this->employeeRequest->refresh();
            $this->form->validate($this->form->rulesForKepOnly());

            $dataForSigning = Repository::employee()->formatEHealthRequest(
                $this->employeeRequest->revision->data
            );

            $base64FileContent = $this->form->getBase64KepFileContent();

            $signedContent = signatureService()->signData(
                $dataForSigning,
                $this->form->password,
                $this->form->knedp,
                $base64FileContent,
                CipherApi::SIGNATORY_INITIATOR_PERSON,
                $this->form->party['taxId']
            );

            if (is_array($signedContent) && isset($signedContent['errors'])) {
                $this->dispatchErrorMessage((array)$signedContent['errors'], __('forms.failed_to_sign_data'));

                return null;
            }

            if ($this->sendSignedContentToEhealth($signedContent)) {
                return response()->redirectToRoute('employee.index');
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return null;
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
                $this->employeeRequest->updated_at        = $ehealthResponse['updated_at'];
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
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $message = $e instanceof ValidationException
            ? __('forms.validation_failed_check_form')
            : __('forms.failed_to_save_employee_unexpected_error');
        session()->flash('error', $message);
        $this->dispatch('employee-form-failed');
    }

    /**
     * Dispatches a structured error message to the session.
     */
    protected function dispatchErrorMessage(array $errors, string $prefix = ''): void
    {
        $errorMessage = collect($errors)->flatten()->implode(', ');
        session()->flash('error', $prefix . $errorMessage);
        $this->dispatch('employee-form-failed');
    }
}
