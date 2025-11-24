<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Livewire\Component;
use Livewire\WithFileUploads;

abstract class ContractComponent extends Component
{
    use FormTrait;
    use WithFileUploads;

    /**
     * List of related divisions
     *
     * @var array
     */
    public array $divisions;

    public function baseMount(LegalEntity $legalEntity): void
    {
        $this->getDictionary();

        $this->divisions = $legalEntity->divisions->toArray();
    }
}
