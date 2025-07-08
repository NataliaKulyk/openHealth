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
    public bool $lockEmailAndTaxId = true;

    public function mount(LegalEntity $legalEntity, int $id, string $type = 'employee'): void
    {
        $this->getDictionary();

        $source = match ($type) {
            'request' => EmployeeRequest::with(['revision', 'party'])->find($id),
            default => Employee::find($id),
        };

        if (!$source) { throw new ModelNotFoundException('Source model not found.'); }

        $this->employee = $source;
        $this->form->hydrate($source);
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
