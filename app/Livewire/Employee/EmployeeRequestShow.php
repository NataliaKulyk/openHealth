<?php
namespace App\Livewire\Employee;

use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeRequestShow extends EmployeeComponent
{
    public EmployeeRequest $employee;

    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();
        $this->employee = $employee_request;


        $this->form->hydrate($this->employee);
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'employee' => $this->employee
        ]);
    }
}
