<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;
    public ?int $employeeRequestId = null;

    /**
     * The mount method now uses the refactored, more explicit loader methods.
     */
    public function mount(LegalEntity $legalEntity, int $employeeId): void
    {
        $this->getDictionary();

        $employee = Employee::find($employeeId);

        if (!$employee) {
            $request = EmployeeRequest::with('revision')->find($employeeId);
            if (!$request) {
                throw new ModelNotFoundException('No Employee or EmployeeRequest found for the given ID.');
            }
            $this->employeeRequest = $request;
            $this->loadEmployeeFromRequest();
        } else {
            // We found the signed Employee.
            $this->employee = $employee;
            $this->employeeId = $employeeId;

            $pendingRequest = EmployeeRequest::where('employee_id', $employeeId)
                ->whereNull('applied_at')
                ->latest()
                ->with('revision')
                ->first();

            if ($pendingRequest) {
                $this->employeeRequest = $pendingRequest;
                $this->loadEmployeeFromRequest();
            } else {
                $this->loadEmployeeFromModel();
            }
        }

        $this->lockPartyFields = true;
        $this->pageTitle = __('forms.editEmployee');
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee ?? $this->employeeRequest,
        ]);
    }
}
