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
    public string $viewMode = 'full_create';

    public function mount(LegalEntity $legalEntity, int $partyId = null): void
    {
        $this->getDictionary();

        if ($partyId) {
            $this->pageTitle = __('forms.addPosition');
            $party = Party::findOrFail($partyId);
            $this->form->populateFromParty($party);
            $this->lockPartyFields = true;
        } else {
            $this->pageTitle = __('forms.addEmployee');
            $this->lockPartyFields = false;
        }
    }

    public function render(): View
    {
        return view('livewire.employee.employee-create', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
            'viewMode' => $this->viewMode,
        ]);
    }
}
