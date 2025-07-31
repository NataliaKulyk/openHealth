<?php

namespace App\Listeners;


use App\Events\ContractFormStateCleared;
use App\Livewire\Contract\ContractIndex;
use Illuminate\Support\Facades\Cache;

class InvalidateContractFormCache
{
    public function handle(ContractFormStateCleared $event): void
    {
        $cacheKey = ContractIndex::CACHE_PREFIX. '-'. $event->legalEntityUuid;
        Cache::forget($cacheKey);

        // Or, using tags for broader invalidation:
        // Cache::tags(['legal-entity:'. $event->legalEntityUuid])->flush();
    }
}
