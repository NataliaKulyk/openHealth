<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->getDictionary();
        $this->lockPartyFields = false;
        $this->pageTitle = __('forms.addEmployee');
    }

    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
        ]);
    }
}
