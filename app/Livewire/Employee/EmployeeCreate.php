<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\View\View;

class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;

    public string $viewMode;

    public function mount(LegalEntity $legalEntity, ?int $partyId = null): void
    {
        $this->getDictionary();

        if ($partyId) {
            $this->pageTitle = __('forms.add_position');
            $party = Party::findOrFail($partyId);
            $this->form->populateFromParty($party);

            $this->lockPartyFields = true;
            $this->viewMode = 'position_only';
        } else {
            // This is the "Create Brand New Employee" scenario.
            $this->pageTitle = __('forms.addEmployee');
            $this->lockPartyFields = false;
            $this->viewMode = 'full_create';
        }
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        // FIX: Pass the $viewMode variable to the view.
        return view('livewire.employee.employee-create', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee, // This is null here, which is correct
            'viewMode' => $this->viewMode,
        ]);
    }
}
