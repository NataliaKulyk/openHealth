<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Core\Arr;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

#[AllowDynamicProperties]
class EmployeeEdit extends AbstractEmployeeFormManager
{
    /**
     * We only expose the ID to the frontend.
     * #[Locked] prevents the user from changing this ID from the browser.
     */
    #[Locked]
    public ?int $employeeId = null;
    public bool $showSignatureModal = false;
    public bool $isLockedDueToSignedRequest = false;

    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $mostRecentPendingRequest = EmployeeRequest::where('employee_id', $employee->id)
            ->whereNull('applied_at')
            ->latest()
            ->first();

        if ($mostRecentPendingRequest) {
            $this->redirectRoute('employee-request.edit', [
                'legalEntity' => $legalEntity->id,
                'employee_request' => $mostRecentPendingRequest->id
            ]);
            return;
        }

        $this->loadDictionaries();
        $this->employee = $employee;
        $this->employeeId = $employee->id;
        $this->isPersonalDataLocked = true;
        $this->loadDivisions($legalEntity);
        $positionName = $this->dictionaries['POSITION'][$employee->position] ?? $employee->position;
        $this->pageTitle = __('forms.edit_employee') . ' "' . $positionName . '" - ' . ($employee->party->fullName ?? '');
        $this->form->hydrate($this->employee);
    }

    public function boot(): void
    {
        if ($this->employeeId) {
            $this->employee = Employee::findOrFail($this->employeeId);
        }
    }

    /**
     * Implements the draft persistence logic for editing an active employee.
     * This creates a NEW EmployeeRequest linked to the existing employee's party and user.
     */
    protected function handleDraftPersistence(): EmployeeRequest
    {
        $preparedData = $this->form->getPreparedData();
        $nestedDataForRevision = $this->mapRevisionData($preparedData);
        $nestedDataForRevision['employee_uuid'] = $this->employee->uuid;

        $employeeRequestData = Arr::only($preparedData, ['position', 'start_date', 'end_date', 'employee_type', 'division_id', 'email']);
        $employeeRequestData['user_id'] = $this->employee->user_id;
        $employeeRequestData['party_id'] = $this->employee->party->id;
        $employeeRequestData['employee_id'] = $this->employee->id;

        if ($this->employeeRequestId) {
            $existingRequest = EmployeeRequest::find($this->employeeRequestId);

            if ($existingRequest && is_null($existingRequest->uuid)) {
                $existingRequest->fill($employeeRequestData)->save();
                $existingRequest->revision?->update(['data' => $nestedDataForRevision]);

                return $existingRequest;
            }
        }

        $newRequest = Repository::employee()->createEmployeeRequestDraft(
            $employeeRequestData,
            legalEntity(),
            $this->employee
        );

        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

        return $newRequest;
    }

    /**
     * The render method. It doesn't need to pass any data, because the template
     * is already bound to the component's public properties (like $this->form).
     */
    public function render(): View
    {
        return view('livewire.employee.employee-edit')->with('pageTitle', $this->pageTitle);
    }
}
