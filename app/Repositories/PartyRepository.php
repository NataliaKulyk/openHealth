<?php

namespace App\Repositories;

use App\Models\Relations\Party;

class PartyRepository
{
    /**
     * Creates a new Party record.
     *
     * @param array $data
     * @return Party
     */
    public function createOrUpdate(array $data): Party
    {
        return Party::updateOrCreate(
            [
                'uuid' => $data['uuid'] ?? null,
            ],
            $data
        );
    }

}
