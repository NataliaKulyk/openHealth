<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Core\Arr;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class EmployeeRequestEdit extends AbstractEmployeeFormManager
{
    #[Locked]
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);
        $this->employeeRequest = $employee_request;
        $this->employeeRequestId = $employee_request->id;
        $this->form->hydrate($this->employeeRequest);
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

        // Logic for updating the EmployeeRequest model itself
        $requestAttributes = Arr::only($preparedData, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
        $this->employeeRequest->fill($requestAttributes)->save();

        // Logic for updating the revision to reflect the latest state
        $nestedDataForRevision = $this->mapRevisionData($preparedData);
        if ($this->employeeRequest->revision) {
            $this->employeeRequest->revision->update(['data' => $nestedDataForRevision]);
        } else {
            // This is a fallback in case a revision doesn't exist for some reason
            $this->saveRevisionForRequest($this->employeeRequest, $nestedDataForRevision);
        }

        return $this->employeeRequest;
    }

    public function render(): View
    {
        return view('livewire.employee.employee-edit');
    }
}
