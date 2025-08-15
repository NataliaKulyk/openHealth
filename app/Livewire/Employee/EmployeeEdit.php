<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    /**
     * We only expose the ID to the frontend.
     * #[Locked] prevents the user from changing this ID from the browser.
     */
    #[Locked]
    public ?int $employeeId = null;

    /**
     * This is the component's entry point. It runs ONCE on initial page load.
     * It receives the model via Route Model Binding.
     */
    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $this->loadDictionaries();

        // 1. We receive the initial model from the route.
        $this->employee = $employee;

        // 2. We store its ID in a public, locked property. This is the only state
        //    that will persist between requests on the frontend.
        $this->employeeId = $employee->id;

        // 3. We populate the public Form Object ($this->form) with the initial data.
        //    The template will be bound to this public Form Object.
        $this->isPersonalDataLocked = true;
        $this->form->hydrate($this->employee);
    }

    /**
     * This method runs BEFORE every subsequent action (like 'save' or 'sign').
     * It ensures that our protected $employee property is always fresh from the database.
     */
    public function boot(): void
    {
        if ($this->employeeId) {
            $this->employee = Employee::findOrFail($this->employeeId);

        }
    }

    /**
     * Since we are editing an active Employee, any save action should result
     * in a NEW EmployeeRequest. So this method returns null, signaling the
     * trait's save() method to enter the 'createNewDraft' logic branch.
     */
    protected function getEmployeeRequestForSave(): ?EmployeeRequest
    {
        return null;
    }

    /**
     * The render method. It doesn't need to pass any data, because the template
     * is already bound to the component's public properties (like $this->form).
     */
    public function render(): View
    {
        return view('livewire.employee.employee-edit');
    }
}
