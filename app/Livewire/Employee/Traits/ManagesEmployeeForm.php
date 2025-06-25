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

    protected ?Employee $employee = null;

    #[Locked]
    public ?int $employeeId = null;

    public ?EmployeeRequest $employeeRequest = null;
    public bool $showSignatureBlock = false;
    public bool $lockPartyFields = false;

    public function loadEmployee(string $viewMode = 'full_edit'): void
    {
        if ($this->employeeId) {
            $employee = Employee::findOrFail($this->employeeId);
            $this->employee = $employee;

            $this->form->populateFromModel($employee, $viewMode);
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
     * Save or Update the employee data, handling different modes.
     *
     * @throws ValidationException
     */
    public function save(): void
    {
        try {
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();
            $preparedDataForDb['legal_entity_uuid'] = legalEntity()->uuid;
            $preparedDataForDb['legal_entity_id']   = legalEntity()->id;

            if ($this->employeeRequest) {
                // Re-saving a pending request
                if ($this->employeeRequest->revision) {
                    $this->employeeRequest->revision->update(['data' => $preparedDataForDb]);
                }
            } else {
                // Creating a new request for the first time
                if ($this->employee) {
                    // This is an EDIT of an existing employee.
                    // We call 'store' with the UUID of the existing employee.
                    $this->employeeRequest = Repository::employee()->store(
                        $preparedDataForDb,
                        legalEntity(),
                        new EmployeeRequest(),
                        $this->employee->uuid
                    );
                } else {
                    // This is a CREATE of a brand new employee.
                    // We call 'store' with isNewRequest = true.
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

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->dispatch('employee-form-failed');
            session()->flash('error', __('forms.validation_failed_check_form'));
            throw $e;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to save employee: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('employee-form-failed');
            session()->flash('error', __('forms.failed_to_save_employee_unexpected_error'));
            $this->showSignatureBlock = false;
            throw $e;
        }
    }

    /**
     * Handles the signing process.
     */
    public function sign()
    {
        try {
            $this->save();
            $this->employeeRequest->refresh();
            $this->form->validate($this->form->rulesForKepOnly());

            $dataForSigning = Repository::employee()->formatEHealthRequest($this->employeeRequest->revision->data);
            $signedContent = signatureService()->signData(
                $dataForSigning,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $this->form->party['taxId']
            );

            if ($this->sendSignedContentToEhealth($signedContent)) {
                return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
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
