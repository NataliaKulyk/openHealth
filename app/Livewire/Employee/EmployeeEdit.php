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
    public string $viewMode = 'full_edit';

    public function mount(LegalEntity $legalEntity, int $employeeId, string $viewMode = 'full_edit'): void
    {
        $this->getDictionary();
        $this->employeeId = $employeeId;
        $this->loadEmployee();
        $this->viewMode = $viewMode;

        if ($this->viewMode === 'party_only') {
            $this->pageTitle = __('forms.editEmployee');
        } else {
            $this->pageTitle = __('forms.editEmployee');
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
