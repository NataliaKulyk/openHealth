<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait SyncsMorphManyRelations
{
    /**
     * Smartly syncs a morphMany relationship.
     * It handles both a single associative array (one record) and an array of arrays (multiple records).
     *
     * @param string     $relation The name of the morphMany relationship method (e.g., 'educations').
     * @param array|null $data     The data to sync. Can be null, a single record, or multiple records.
     *
     * @return void
     * @throws \Throwable
     */
    public function syncMany(string $relation, ?array $data): void
    {
        if (is_null($data)) {
            $dataToCreate = [];
        } elseif (!empty($data) && !array_is_list($data)) {
            // If $data is not empty and is NOT a list, it's a single associative array.
            // Wrap it in an array to make it compatible with createMany.
            $dataToCreate = [$data];
        } else {
            // It's already an array of records (a list) or an empty array.
            $dataToCreate = $data;
        }

        DB::transaction(function () use ($relation, $dataToCreate) {
            $this->$relation()->delete();

            if (!empty($dataToCreate)) {
                $this->$relation()->createMany($dataToCreate);
            }
        });
    }
}
