<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Classes\eHealth\EHealth;
use App\Enums\Contract\Type;
use App\Models\Contract;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class ContractIndex extends Component
{
    use FormTrait;
    use WithPagination;

    public array $typeFilter = [];
    public bool $isFiltersApplied = false;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->typeFilter = Type::values();
    }

    public function search(): void
    {
        $this->resetPage();
        $this->isFiltersApplied = true;
    }

    public function resetFilters(): void
    {
        $this->reset();
    }

    #[Computed]
    public function contracts(): LengthAwarePaginator
    {
        // Filtering by the current Legal Entity ID in our DB
        $query = Contract::whereLegalEntityId(legalEntity()->id)
            ->orderBy('inserted_at', 'desc'); // Show newest first

        if ($this->isFiltersApplied) {
            // Add custom filters here if needed
        }

        return $query->paginate(config('pagination.per_page', 15));
    }

    /**
     * Synchronizes contracts from eHealth API.
     * Automatically determines the contract type based on Legal Entity type.
     */
    public function sync(): void
    {
        try {
            // 1. Determine contract type (Reimbursement vs Capitation)
            // Assuming LegalEntity model has constants TYPE_PHARMACY and TYPE_MSP
            $contractType = legalEntity()->type->name === LegalEntity::TYPE_PHARMACY
                ? 'reimbursement'
                : 'capitation';

            // 2. Fetch list from eHealth
            $response = EHealth::contractRequest()->getMany($contractType, [
                'page_size' => 50,
                'contractor_legal_entity_id' => legalEntity()->uuid
            ]);

            $items = $response->getData();

            // 3. Loop and Save using Repository
            foreach ($items as $item) {
                // Determine 'type' explicitly if API doesn't return it in the list view
                if (!isset($item['type'])) {
                    $item['type'] = strtoupper($contractType);
                }

                Repository::contract()->saveFromEHealth($item);
            }

            $this->isFiltersApplied = true;
            session()->flash('success', 'Дані успішно синхронізовано (' . count($items) . ' записів).');

        } catch (\Exception $e) {
            session()->flash('error', 'Помилка синхронізації: ' . $e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.contract.contract-index', [
            'contracts' => $this->contracts
        ]);
    }
}
