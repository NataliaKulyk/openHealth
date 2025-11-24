<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Livewire\Contract\Forms\ReimbursementContractRequestForm as Form;
use App\Models\LegalEntity;
use Illuminate\View\View;

class CapitationContractCreate extends ContractComponent
{
    public Form $form;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount($legalEntity);
    }

    public function render(): View
    {
        return view('livewire.contract.capitation-contract-create');
    }
}
