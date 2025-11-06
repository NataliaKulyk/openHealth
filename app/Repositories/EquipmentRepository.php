<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Arr;
use App\Models\Equipment;
use Throwable;

class EquipmentRepository
{
    /**
     * Store data after successful creating in EHealth.
     *
     * @param  array  $data
     * @return Equipment
     * @throws Throwable
     */
    public function store(array $data): Equipment
    {
        $equipment = Equipment::create(Arr::except($data, ['names', 'properties']));
        $equipment->names()->createMany($data['names']);

        return $equipment;
    }
}
