<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class EmployeeShow extends EmployeeComponent
{
    protected Employee $employee;

    #[Locked]
    public ?int $employeeId = null;

    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $this->loadDictionaries();
        $this->employee = $employee;
        $this->employeeId = $employee->id;
        $this->form->hydrate($this->employee);
    }

    public function boot(): void
    {
        if($this->employeeId){
            $this->employee = Employee::findOrFail($this->employeeId);
        }
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'employee' => $this->employee
        ]);
    }
}
