<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public string $pageTitle;

    public ?EmployeeRequest $employeeRequest = null;
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->loadDictionaries();
        $this->pageTitle = __('forms.add_employee');
        $this->isPersonalDataLocked = false;
    }

    public function render(): View
    {
        return view('livewire.employee.employee-create');
    }
}
