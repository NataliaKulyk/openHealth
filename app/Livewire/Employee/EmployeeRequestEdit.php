<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeRequestEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();
        $this->isPersonalDataLocked = true;

        $this->employeeRequest = $employee_request;
        $this->employeeRequestId = $employee_request->id;

        $this->form->hydrate($this->employeeRequest);
    }


    public function render(): View
    {
        return view('livewire.employee.employee-edit', [
            'employee' => $this->employeeRequest
        ]);
    }
}
