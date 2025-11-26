<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Livewire\Contract\Forms\ReimbursementContractRequestForm as Form;
use App\Models\LegalEntity;
use Illuminate\View\View;

class CapitationContractCreate extends ContractComponent
{
    public Form $form;

    public array $legalEntities;

    protected array $dictionaryNames = [
        'CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT',
        'MEDICAL_SERVICE',
    ];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount($legalEntity);

        $this->legalEntities = LegalEntity::get(['id', 'edr'])->toArray();
    }

    public function render(): View
    {
        return view('livewire.contract.capitation-contract-create');
    }
}
