<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class EmployeeRequestShow extends EmployeeComponent
{
    protected EmployeeRequest $employee;

    #[Locked]
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);
        $this->employee = $employee_request;
        $this->employeeRequestId = $employee_request->id;
        $this->form->hydrate($this->employee);
    }

    public function boot(): void
    {
        if ($this->employeeRequestId) {
            $this->employee = EmployeeRequest::findOrFail($this->employeeRequestId);
        }
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'employee' => $this->employee
        ]);
    }
}
