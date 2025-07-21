<?php
namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use Traits\ManagesEmployeeForm;

    public string $pageTitle;
    public ?EmployeeRequest $employeeRequest = null;
    public ?int $employeeRequestId = null;

    public function mount(LegalEntity $legalEntity, int $id, string $type = 'employee'): void
    {
        $this->loadDictionaries();

        $source = match ($type) {
            'request' => EmployeeRequest::findOrFail($id),
            default => Employee::findOrFail($id),
        };

        $this->authorize('update', $source);

        $this->isPersonalDataLocked = true;

        if ($source instanceof Employee) {
            $this->employee = $source;
        } else {
            $this->employeeRequest = $source;
            $this->employeeRequestId = $source->id;
        }

        $this->form->hydrate($source);
        $this->pageTitle = __('forms.edit_employee');
    }

    public function render(): View
    {
        return view('livewire.employee.employee');
    }
}
