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
    public bool $lockPartyFields = true;

    /**
     * FIX: The mount method is now a "smart loader".
     * It can handle both finalized Employee models and pending EmployeeRequest models.
     */
    public function mount(LegalEntity $legalEntity, int $id): void
    {
        $record = Employee::with('party')->find($id);

        if (!$record) {
            $record = EmployeeRequest::with(['revision', 'party'])->find($id);

            if (!$record) {
                throw new ModelNotFoundException('No Employee or EmployeeRequest found for the given ID.');
            }
        }

        $this->employee = $record;
        $this->getDictionary();

        if ($this->employee instanceof EmployeeRequest) {
            $this->form->populateFromRequest($this->employee);
        } else {
            $this->form->populateFromModel($this->employee);
        }

        $this->pageTitle = __('forms.viewEmployee');
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
