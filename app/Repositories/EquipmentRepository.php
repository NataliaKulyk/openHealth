<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Arr;
use App\Models\Equipment;
use App\Models\EquipmentName;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
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

    /**
     * Update existed record with EHealth data.
     *
     * @param  array  $data
     * @return Equipment
     * @throws Throwable
     */
    public function update(array $data): Equipment
    {
        if (empty($data['id'])) {
            throw new InvalidArgumentException('Equipment ID is required for update.');
        }

        $equipment = Equipment::findOrFail($data['id']);

        $equipment->update(Arr::except($data, ['id', 'names', 'properties']));

        // Update names
        $equipment->names()->delete();
        $equipment->names()->createMany($data['names']);

        return $equipment;
    }

    /**
     * Sync equipment and related names.
     *
     * @param  array  $items
     * @return void
     * @throws Throwable
     */
    public function sync(array $items): void
    {
        DB::transaction(static function () use ($items) {
            // Sync equipment
            Equipment::upsert(
                collect($items)->map(static fn (array $item) => Arr::except($item, ['names']))->toArray(),
                'uuid',
                Arr::except(new Equipment()->getFillable(), ['names'])
            );

            // Get equipments by uuid
            $equipments = Equipment::whereIn('uuid', collect($items)->pluck('uuid'))
                ->get(['id', 'uuid'])
                ->keyBy('uuid');

            // Map names data
            $namesData = collect($items)->flatMap(static function (array $item) use ($equipments) {
                $equipmentId = $equipments->get($item['uuid'])?->id;
                if (!$equipmentId) {
                    return [];
                }

                return collect($item['names'])->map(
                    static fn (array $name) => array_merge($name, ['equipment_id' => $equipmentId])
                );
            });

            // Delete old and insert new
            if ($namesData->isNotEmpty()) {
                EquipmentName::whereIn('equipment_id', $equipments->pluck('id'))->delete();
                EquipmentName::insert($namesData->toArray());
            }
        });
    }
}
