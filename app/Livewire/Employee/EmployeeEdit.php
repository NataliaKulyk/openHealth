<?php
namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use Illuminate\View\View;

#[AllowDynamicProperties]
class EmployeeEdit extends EmployeeComponent
{
    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $this->loadDictionaries();
        $this->isPersonalDataLocked = true;
        $this->employee = $employee;
        $this->form->hydrate($this->employee);
    }

    public function render(): View
    {
        return view('livewire.employee.employee-edit', [
            'employee' => $this->employee
        ]);
    }
}
