<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Models\Contract;
use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Illuminate\View\View;
use Livewire\Component;

class ContractShow extends Component
{
    use FormTrait;

    public Contract $contract;

    // Used to display the legal entity name in the header or details
    public string $legalEntityName = '';

    public function mount(LegalEntity $legalEntity, Contract $contract): void
    {
        // Ensure the contract belongs to the current Legal Entity (double check besides middleware)
        if ($contract->legal_entity_id !== $legalEntity->id) {
            abort(404);
        }

        $this->contract = $contract;

        // Load Dictionary for Statuses if needed in the view (or use Enum directly)
        // $this->getDictionary();

        // Decode EDR data to show clear company name if needed
        $edrData = is_string($legalEntity->edr)
            ? json_decode($legalEntity->edr, true, 512, JSON_THROW_ON_ERROR)
            : (is_array($legalEntity->edr) ? $legalEntity->edr : []);

        $this->legalEntityName = $edrData['name'] ?? __('Невідома назва');
    }

    public function render(): View
    {
        return view('livewire.contract.contract-show', [
            'contract' => $this->contract
        ]);
    }
}
