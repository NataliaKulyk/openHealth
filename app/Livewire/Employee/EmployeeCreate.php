<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->loadDictionaries();
        $this->isPersonalDataLocked = false;
    }

    public function render(): View
    {
        return view('livewire.employee.employee');
    }

    protected function getEmployeeRequestForSave(): ?EmployeeRequest
    {
        if (!empty($this->employeeRequestId)) {
            return EmployeeRequest::find($this->employeeRequestId);
        }

        return null;
    }
}
