<?php

namespace App\Livewire\Employee;

use App\Core\Arr;
use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\View\View;

class EmployeePositionAdd extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;
    public ?int $employeeRequestId = null;

    /**
     * The mount method now uses a hybrid approach to populate form data.
     * It prioritizes live data from Party relations, but falls back to the
     * latest EmployeeRequest revision if data (like phones or documents) is missing.
     */
    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->getDictionary();
        $this->form->populateFromParty($party);

        $needsRevisionCheck = empty($this->form->documents) || empty($this->form->party['phones']) || empty($this->form->party['phones'][0]['number']);

        if ($needsRevisionCheck) {
            $latestRequest = $party->employeeRequests()->latest()->first();

            if ($latestRequest && $latestRequest->revision) {
                $revisionData = $latestRequest->revision->data ?? [];

                if (empty($this->form->party['phones']) || empty($this->form->party['phones'][0]['number'])) {
                    $phonesData = $revisionData['phones'] ?? [];
                    if (!empty($phonesData)) {
                        $this->form->party['phones'] = Arr::toCamelCase($phonesData);
                    }
                }

                if (empty($this->form->documents)) {
                    $documentsData = $revisionData['documents'] ?? [];
                    if (!empty($documentsData)) {
                        $this->form->documents = Arr::toCamelCase($documentsData);
                    }
                }
            }
        }

        $this->form->resetPositionFields();

        $this->pageTitle = __('forms.addPosition');
    }

    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
        ]);
    }
}
