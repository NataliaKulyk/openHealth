<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public string $pageTitle;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->loadDictionaries();
        $this->isPersonalDataLocked = false;

        $this->pageTitle = __('forms.add_employee');
    }

    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
        ]);
    }
}
