<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

class EmployeePositionAdd extends EmployeeComponent
{
    use ManagesEmployeeForm;

    #[Locked]
    public ?int $partyId = null;
    protected ?Party $party = null;

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);
        $this->isPersonalDataLocked = true;
        $this->party = $party;
        $this->partyId = $party->id;
        $this->form->hydrate($this->party);
        $this->form->resetPositionFields();
    }

    public function boot(): void
    {
        if ($this->partyId) {
            $this->party = Party::findOrFail($this->partyId);
        }
    }

    public function render(): View
    {
        return view('livewire.employee.employee');
    }

    /**
     * Finds and returns the existing draft request if its ID is known.
     * Returns null only if this is the very first save action for a new form.
     */
    protected function getEmployeeRequestForSave(): ?EmployeeRequest
    {
        if (!empty($this->employeeRequestId)) {
            return EmployeeRequest::find($this->employeeRequestId);
        }

        return null;
    }
}
