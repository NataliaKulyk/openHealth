<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class EmployeeShow extends EmployeeComponent
{
    public Employee|EmployeeRequest $employee;
    public string $pageTitle;
    public bool $isReadOnly = true;

    public function mount(LegalEntity $legalEntity, int $id, string $type = 'employee'): void
    {
        $this->loadDictionaries();

        $source = match ($type) {
            'request' => EmployeeRequest::findOrFail($id),
            default => Employee::findOrFail($id),
        };

        $this->authorize('view', $source);

        $this->employee = $source;
        $this->form->hydrate($source);
        $this->pageTitle = __('forms.view_employee');
    }

    public function render(): View
    {

        return view('livewire.employee.employee-show', [
            'employee' => $this->employee
        ]);
    }
}
