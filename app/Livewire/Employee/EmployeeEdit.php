<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity, int $id, string $type = 'employee'): void
    {
        $this->getDictionary();

        $source = match ($type) {
            'request' => EmployeeRequest::with(['revision', 'party'])->find($id),
            default => Employee::find($id),
        };

        if (!$source) { throw new ModelNotFoundException('Source model not found.'); }

        if ($source instanceof Employee) {
            $this->employee = $source;
            $this->employeeId = $source->id;
        } else {
            $this->employeeRequest = $source;
        }

        $this->form->hydrate($source);
        $this->lockEmailAndTaxId  = true;
        $this->pageTitle = __('forms.editEmployee');
    }

    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee ?? $this->employeeRequest,
        ]);
    }
}
