<?php

namespace App\Livewire\Employee;

use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\View\View;

class EmployeePositionAdd extends EmployeeComponent
{
    public ?int $employeeRequestId = null;
    public string $pageTitle;

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->loadDictionaries();
        $this->isPersonalDataLocked = true;
        $this->form->hydrate($party);
        $this->form->resetPositionFields();

        $this->pageTitle = __('forms.add_position');
    }

    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
        ]);
    }
}
