<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class ContractComponent extends Component
{
    use FormTrait;
    use WithFileUploads;

    public string $legalEntityName;

    public string $contractorFullName;

    public bool $showSignatureModal = false;

    public function baseMount(LegalEntity $legalEntity): void
    {
        $this->getDictionary();

        $this->form->contractorLegalEntityId = $legalEntity->uuid;
        $this->legalEntityName = $legalEntity->edr['name'];

        $contractorData = Auth::user()->employees()
            ->contractors($legalEntity->id)
            ->get(['uuid', 'party_id'])
            ->first();

        if (empty($contractorData)) {
            abort(403, __('Співробітника з відповідними доступами не знайдено.'));
        }

        $this->contractorFullName = $contractorData->fullName;
        $this->form->contractorOwnerId = $contractorData->uuid;
    }

    public function openSignatureModal(): void
    {
        $this->showSignatureModal = true;
    }
}
