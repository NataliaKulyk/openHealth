<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use AllowDynamicProperties;
use App\Enums\Status;
use App\Livewire\Employee\AbstractEmployeeFormManager;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Repositories\Repository;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

#[AllowDynamicProperties]
class PartyEdit extends AbstractEmployeeFormManager
{
    #[Locked]
    public ?int $partyId = null;

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);
        $this->party = $party;
        $this->partyId = $party->id;
        $this->pageTitle = __('forms.edit_personal_data') . ' - ' . ($party->fullName ?? '');
        $employee = $party->employees()->latest('start_date')->first();
        $this->form->hydrate($employee ?? $party);
        $this->isPartyDataPartiallyLocked = true;
        $this->isPositionDataLocked = true;
    }

    public function boot(): void
    {
        if ($this->partyId) {
            $this->party = Party::findOrFail($this->partyId);
        }
    }

    #[Computed]
    public function partyPositions(): Collection
    {
        return $this->party->employees()->with('division')->get();
    }

    /**
     * Creates a draft that contains updated personal data (Party/Documents)
     * AND unchanged position data (from blocked fields).
     */
    protected function handleDraftPersistence(): EmployeeRequest
    {

        $employee = $this->party->employees()
            ->where('status', '!=', Status::DISMISSED->value)
            ->latest('start_date')
            ->firstOrFail();

        $preparedData = $this->form->getPreparedData();

        $nestedDataForRevision = $this->mapRevisionData($preparedData);

        $employeeRequestData = [
            'user_id' => $employee->user_id,
            'party_id' => $this->party->id,
            'employee_id' => $employee->id,
            'position' => $employee->position,
            'employee_type' => $employee->employee_type,
            'start_date' => $employee->start_date?->format('Y-m-d'),
            'division_id' => $employee->division_id,
            'email' => $employee->user?->email,
        ];

        if ($this->employeeRequestId) {
            $existingRequest = EmployeeRequest::find($this->employeeRequestId);
            if ($existingRequest && is_null($existingRequest->uuid)) {
                $existingRequest->fill($employeeRequestData)->save();
                $existingRequest->revision?->update(['data' => $nestedDataForRevision]);

                return $existingRequest;
            }
        }

        $newRequest = Repository::employee()->createEmployeeRequestDraft($employeeRequestData, legalEntity());
        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

        return $newRequest;
    }

    public function render(): View
    {
        return view('livewire.party.party-edit')->with('pageTitle', $this->pageTitle);
    }
}
