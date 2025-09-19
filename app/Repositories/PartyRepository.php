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
        // Exclude 'documents' and 'phones' from the data array (mostly need for LegalEntity creation/editing)
        $data = array_diff_key($data, array_flip(['documents', 'phones']));

        return Party::updateOrCreate(
            [
                'uuid' => $data['uuid'] ?? null,
            ],
            $data
        );
    }

}
