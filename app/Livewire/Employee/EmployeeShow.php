<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeShow extends EmployeeComponent
{
    public Employee|EmployeeRequest $employee;
    public bool $isReadOnly = true;

    public function mount(LegalEntity $legalEntity, $id): void
    {
        if (request()->routeIs('employee.*')) {
            $this->employee = $legalEntity->employees()->findOrFail($id);
        } else {
            $this->employee = $legalEntity->employeeRequests()->findOrFail($id);
        }

        // Явно викликаємо авторизацію
        $this->authorize('view', $this->employee);

        $this->loadDictionaries();
        $this->form->hydrate($this->employee);
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'employee' => $this->employee
        ]);
    }
}
