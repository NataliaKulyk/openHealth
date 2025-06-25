<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\View\View;

class AddPosition extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle = 'Додавання нової посади';

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->getDictionary();

        $this->form->populateFromParty($party);

        $this->lockPartyFields = true;
    }

    public function render(): View
    {
        // Now points to the new unified view
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
        ]);
    }
}
