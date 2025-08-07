<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Livewire\Declaration\Forms\DeclarationForm as Form;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

class DeclarationCreate extends DeclarationComponent
{
    use WithFileUploads;

    public Form $form;

    #[Locked]
    public int $patientId;

    public bool $showAuthModal = false;
    public bool $showApproveModal = false;
    public bool $showSignModal = false;
    public bool $showSignatureModal = false;

    /**
     * Patient full name.
     * @var string
     */
    public string $patientFullName;

    /**
     * List of patient authentication methods.
     * @var array
     */
    public array $authMethods;

    protected string $patientUuid;

    /**
     * UUID of created declaration request.
     * @var string
     */
    public string $declarationRequestUuid;

    public array $dictionaryNames = ['POSITION'];

    public array $employeesInfo;

    public string $printableContent;

    public function mount(LegalEntity $legalEntity, int $patientId): void
    {
        $this->patientId = $patientId;
        $this->getDictionary();

        $patient = Person::select(['uuid', 'first_name', 'last_name', 'second_name'])
            ->where('id', $this->patientId)
            ->firstOrFail();
        // TODO: після мержа іншого ПР зробити $patient->fullName
        $this->patientFullName = $patient->first_name . ' ' . $patient->last_name;
        $this->patientUuid = $patient->uuid;
        //        dd($this->patientUuid);

        $this->setEmployeesInfo();

        $this->form->personId = $this->patientUuid;
        $this->authMethods = $this->getAuthMethods();
    }

    public function openSignatureModal()
    {
        $this->showSignModal = false;
        $this->showSignatureModal = true;
    }

    /**
     * Create a validated application(declaration request).
     *
     * @return void
     */
    public function create(): void
    {
        if (empty($this->form->divisionId)) {
            $this->form->divisionId = optional(
                collect($this->employeesInfo)->firstWhere('employeeId', $this->form->employeeId)
            )['divisionId'] ?? '';
        }

        try {
            $validated = $this->form->validate($this->form->rulesForCreating());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::declarationRequest()->create(data: removeEmptyKeys(Arr::toSnakeCase($validated)));

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while creating declaration request');
                $this->flashGeneralError();

                return;
            }

            if ($response->getStatusCode() === 200) {
                $this->declarationRequestUuid = $response->getData()['id'];
                $this->showAuthModal = true;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while creating declaration request');
            $this->flashGeneralError();

            return;
        }
    }

    /**
     * Resend SMS to patient.
     *
     * @return void
     */
    public function resendSms(): void
    {
        try {
            $response = EHealth::declarationRequest()->resendAuthOtp('$this->declarationRequestUuid');

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while resending auth OTP on declaration request');
                $this->flashGeneralError();

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while resending sms to person');
            $this->flashGeneralError();

            return;
        }

        if ($response->getData()['status'] === 'new') {
            $this->dispatch('flashMessage', [
                'message' => __('SMS успішно надіслано!'),
                'type' => 'success'
            ]);
        }
    }

    /**
     * Send approving request with verified code.
     *
     * @return void
     */
    public function approve(): void
    {
        try {
            $validated = $this->form->validate($this->form->rulesForApproving());
        } catch (ValidationException $e) {
            $this->dispatch('flashMessage', [
                'message' => $e->validator->errors()->first(),
                'type' => 'error'
            ]);

            return;
        }

        try {
            $response = EHealth::declarationRequest()
                ->approve($this->declarationRequestUuid, Arr::toSnakeCase($validated));

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while approving declaration request');
                $this->flashGeneralError();

                return;
            }

            if ($response->getStatusCode() === 201) {
                $this->printableContent = $response->getData()['data_to_be_signed']['content'];
                $this->showAuthModal = false;
                $this->showApproveModal = true;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while approving declaration request');
            $this->flashGeneralError();

            return;
        }
    }

    public function sign(): void
    {
        //
    }

    /**
     * Get all employees with divisions and set that info.
     *
     * @return void
     */
    protected function setEmployeesInfo(): void
    {
        $employees = Auth::user()?->employees()->whereNotNull('division_id')->with('division')->get();
        $this->employeesInfo = $employees->map(static fn (Employee $employee) => [
            'employeeId' => $employee->uuid,
            'fullName' => $employee->fullName,
            'position' => $employee->position,
            'divisionId' => $employee->division->uuid,
            'divisionName' => $employee->division->name
        ])->toArray();

        if (count($this->employeesInfo) === 1) {
            $this->form->employeeId = $this->employeesInfo[0]['employeeId'];
            $this->form->divisionId = $this->employeesInfo[0]['divisionId'];
        }
    }

    /**
     * Get patient authentication methods.
     *
     * @return array
     */
    protected function getAuthMethods(): array
    {
        try {
            $response = EHealth::person()->getAuthMethods($this->patientUuid);

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while getting patient auth methods');
                $this->flashGeneralError();

                return [];
            }

            return $response->getData();
        } catch (ConnectionException $e) {
            Log::channel('e_health_errors')->error('Error while getting auth methods', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->flashGeneralError();

            return [];
        }
    }
}
