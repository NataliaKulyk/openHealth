<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeShow extends EmployeeComponent
{
    public Employee $employee;
    public string $pageTitle;

    /**
     * This property is added to satisfy the shared Blade partial (_employee.blade.php),
     * which checks for this property to conditionally disable fields.
     * For a "show" page, fields are always considered "locked".
     */
    public bool $lockPartyFields = true;

    /**
     * The mount method for the Show component.
     * It uses Route-Model binding to get the Employee model.
     */
    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $this->employee = $employee->load([
                                              'party.phones',
                                              'party.documents',
                                              'educations',
                                              'specialities',
                                              'qualifications',
                                              'scienceDegrees',
                                              'division'
                                          ]);

        $this->getDictionary();
        $this->form->populateFromModel($this->employee);

        $this->pageTitle = __('forms.view_employee');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
        ]);
    }
}
