<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class EmployeeRequestEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    #[Locked]
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);
        $this->isPersonalDataLocked = true;
        $this->employeeRequest = $employee_request;
        $this->employeeRequestId = $employee_request->id;
        $this->form->hydrate($this->employeeRequest);
    }

    public function boot(): void
    {
        if ($this->employeeRequestId) {
            $this->employeeRequest = EmployeeRequest::findOrFail($this->employeeRequestId);
        }
    }

    protected function getEmployeeRequestForSave(): ?EmployeeRequest
    {
        return $this->employeeRequest;
    }

    public function render(): View
    {
        return view('livewire.employee.employee-edit');
    }
}
