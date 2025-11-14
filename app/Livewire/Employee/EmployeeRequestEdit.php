<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Core\Arr;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Illuminate\View\View;

#[AllowDynamicProperties]
class EmployeeRequestEdit extends AbstractEmployeeFormManager
{
    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);
        $this->employeeRequest = $employee_request;
        $this->employeeRequestId = $employee_request->id;
        $employeeName = $employee_request->party->fullName ?? ($employee_request->employee->party->fullName ?? '');
        $positionName = $this->dictionaries['POSITION'][$employee_request->position] ?? $employee_request->position;
        $this->pageTitle = __('forms.edit_employee_request') . ' "' . $positionName . '" - ' . $employeeName;


        $this->form->hydrate($this->employeeRequest);

        if (!is_null($employee_request->uuid)) {
            $this->isPositionDataLocked = true;

            session()->flash('info', __('forms.signed_request_can_edit_party_only'));
        }
    }

    public function boot(): void
    {
        if ($this->employeeRequestId) {
            // Ensure the model instance is always fresh
            $this->employeeRequest = EmployeeRequest::findOrFail($this->employeeRequestId);
        }
    }

    /**
     * Implements the draft persistence logic for editing an existing EmployeeRequest.
     * It updates the existing draft and its revision.
     */
    protected function handleDraftPersistence(): EmployeeRequest
    {
        $preparedData = $this->form->getPreparedData();
        $nestedDataForRevision = $this->mapRevisionData($preparedData);

        if (!is_null($this->employeeRequest->uuid)) {

            $employeeRequestData = Arr::only($preparedData, ['position', 'start_date', 'end_date', 'employee_type', 'division_id', 'email']);

            $employeeRequestData['user_id'] = $this->employeeRequest->user_id;
            $employeeRequestData['party_id'] = $this->employeeRequest->party_id;
            $employeeRequestData['employee_id'] = $this->employeeRequest->employee_id;

            $newRequest = Repository::employee()->createEmployeeRequestDraft(
                $employeeRequestData,
                legalEntity(),
                $this->employeeRequest->employee
            );

            $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

            $this->employeeRequestId = $newRequest->id;


            return $newRequest;

        }

        $requestAttributes = Arr::only($preparedData, ['position', 'employee_type', 'start_date', 'end_date', 'division_id', 'email']);
        $this->employeeRequest->fill($requestAttributes)->save();

        if ($this->employeeRequest->revision) {
            $this->employeeRequest->revision->update(['data' => $nestedDataForRevision]);
        } else {
            $this->saveRevisionForRequest($this->employeeRequest, $nestedDataForRevision);
        }

        return $this->employeeRequest;
    }

    public function render(): View
    {
        return view('livewire.employee.employee-edit')->with('pageTitle', $this->pageTitle);
    }
}
