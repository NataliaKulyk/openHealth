<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Core\Arr;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class EmployeeEdit extends AbstractEmployeeFormManager
{
    /**
     * We only expose the ID to the frontend.
     * #[Locked] prevents the user from changing this ID from the browser.
     */
    #[Locked]
    public ?int $employeeId = null;

    /**
     * @var bool
     */
    public bool $showSignatureModal = false;
    /**
     * This is the component's entry point. It runs ONCE on initial page load.
     * It receives the model via Route Model Binding.
     */
    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $this->loadDictionaries();
        $this->employee = $employee;
        $this->employeeId = $employee->id;
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
     * Implements the draft persistence logic for editing an active employee.
     * This creates a NEW EmployeeRequest linked to the existing employee's party and user.
     */
    protected function handleDraftPersistence(): EmployeeRequest
    {
        // This logic is very similar to EmployeePositionAdd
        $party = $this->employee->party;
        $preparedData = $this->form->getPreparedData();
        $employeeRequestData = Arr::only($preparedData, ['position', 'start_date', 'end_date', 'employee_type', 'division_id', 'email']);

        $employeeRequestData['user_id'] = $party->user_id;
        $employeeRequestData['party_id'] = $party->id;
        // You might also want to link it to the employee being edited
        // $employeeRequestData['employee_id'] = $this->employee->id;

        $newRequest = Repository::employee()->createEmployeeRequestDraft($employeeRequestData, legalEntity());

        $nestedDataForRevision = $this->mapRevisionData($preparedData);
        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

        return $newRequest;
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
