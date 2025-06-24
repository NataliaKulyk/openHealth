<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;
    public string $viewMode = 'full_edit';

    public function mount(LegalEntity $legalEntity, int $employeeId, string $viewMode = 'full_edit'): void
    {
        $this->getDictionary();
        $this->employeeId = $employeeId;
        $this->viewMode = $viewMode;

        // The loadEmployee method now accepts the mode and handles the logic.
        $this->loadEmployee($viewMode);

        if ($this->viewMode === 'add_position') {
            $this->pageTitle = __('forms.add_position');
            $this->lockPartyFields = true;
            $this->employeeRequest = null; // Important for forcing creation on save
        } else {
            $this->pageTitle = __('forms.edit_employee_position');
            $this->lockPartyFields = true;
        }
    }

    public function render(): View
    {
        return view('livewire.employee.employee-edit', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
            'viewMode' => $this->viewMode,
        ]);
    }
}
