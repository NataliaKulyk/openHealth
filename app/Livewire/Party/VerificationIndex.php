<?php

declare(strict_types=1);

namespace App\Livewire\Party;

use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Livewire\Component;
use Livewire\WithPagination;

class VerificationIndex extends Component
{
    use WithPagination;

    public LegalEntity $legalEntity;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntity = $legalEntity;
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
    {
        $parties = Party::query()
            ->whereHas('employees', function ($query) {
                $query->where('legal_entity_id', $this->legalEntity->id);
            })
            ->paginate(15);

        return view('livewire.party.verification-index', [
            'parties' => $parties,
        ]);
    }
}
