<?php
namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeShow extends EmployeeComponent
{
    public Employee $employee;
    public string $pageTitle;

    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $this->employee = $employee->load(
            [
                'party.phones', 'party.documents', 'educations',
                'specialities', 'qualifications', 'scienceDegrees', 'division',
            ]
        );

        $this->getDictionary();
        $this->form->populateFromModel($this->employee);

        $this->pageTitle = __('forms.viewEmployee');
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
        ]);
    }
}
