<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SpecialityRepository
{
    /**
     *
     * @param Model      $model
     * @param array|null $specialitiesData
     *
     * @return void
     * @throws \Throwable
     */
    public function syncSpecialities(Model $model, ?array $specialitiesData): void
    {
        $specialitiesData = $specialitiesData ?? [];

        DB::transaction(function () use ($model, $specialitiesData) {
            $model->specialities()->delete();

            if (!empty($specialitiesData)) {
                $model->specialities()->createMany($specialitiesData);
            }
        });
    }
}
