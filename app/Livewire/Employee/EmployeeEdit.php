<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;

    /**
     * The mount method is now simplified. Its only job is to load
     * the employee data for editing.
     */
    public function mount(LegalEntity $legalEntity, int $employeeId): void
    {
        $this->getDictionary();
        $this->employeeId = $employeeId;

        // The trait method handles finding the model and populating the form fully.
        $this->loadEmployee();

        // Personal data is locked by default when editing.
        $this->lockPartyFields = true;

        // Set the title dynamically after the employee is loaded.
        if ($this->employee) {
            $this->pageTitle = __('forms.edit_employee_position') . ': ' . $this->employee->fullName;
        } else {
            $this->pageTitle = __('forms.edit_employee_position');
        }
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        // Now points to the new unified view
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
        ]);
    }
}
