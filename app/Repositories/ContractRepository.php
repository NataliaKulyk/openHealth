<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Classes\eHealth\Api\ContractRequest;
use App\Models\Contract;

class ContractRepository
{
    /**
     * Saves or updates a contract based on data received from E-Health API.
     * Uses the API class mapper to normalize the data structure.
     *
     * @param  array  $eHealthData  Raw data from eHealth API response.
     * @return Contract
     */
    public function saveFromEHealth(array $eHealthData): Contract
    {
        // 1. Use the mapper from the API class
        // Laravel container automatically resolves ContractRequest dependency
        $mapper = app(ContractRequest::class);
        $attributes = $mapper->mapCreate($eHealthData);

        // 2. Add local context (legal_entity_id) which is missing in API response
        // Assumes 'legalEntity()' global helper is available
        $attributes['legal_entity_id'] = legalEntity()->id;

        // 3. Persist to Database (Update existing by UUID or Create new)
        return Contract::updateOrCreate(
            ['uuid' => $attributes['uuid']],
            $attributes
        );
    }
}
