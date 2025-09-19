<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\LegalEntity;
use App\Models\License;

class LicenseRepository
{
    /**
     * Store data after successful creating in EHealth.
     *
     * @param  array  $data
     * @return License
     */
    public function store(array $data): License
    {
        $data = $this->mapUuidsToIds($data);

        return License::create($data);
    }

    /**
     * Update data after successful updating in EHealth.
     *
     * @param  array  $data
     * @return bool
     */
    public function update(array $data): bool
    {
        $data = $this->mapUuidsToIds($data);

        return License::update($data);
    }

    /**
     * Map uuids to ids for setting relationship.
     *
     * @param  array  $data
     * @return array
     */
    private function mapUuidsToIds(array $data): array
    {
        $data['uuid'] = $data['id'];
        unset($data['id']);

        $data['legal_entity_id'] = LegalEntity::where('uuid', $data['legal_entity_id'])
            ->pluck('id')
            ->firstOrFail();

        return $data;
    }
}
