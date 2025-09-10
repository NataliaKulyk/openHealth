<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ScienceDegreeRepository
{
    /**
     *
     * @param Model      $model
     * @param array|null $scienceDegreesData
     *
     * @return void
     * @throws \Throwable
     */
    public function syncScienceDegrees(Model $model, ?array $scienceDegreesData): void
    {
        $scienceDegreesData = $scienceDegreesData ?? [];

        DB::transaction(function () use ($model, $scienceDegreesData) {
            $model->scienceDegrees()->delete();

            if (!empty($scienceDegreesData)) {
                if (isset($scienceDegreesData['degree'])) {
                    $model->scienceDegrees()->create($scienceDegreesData);
                } else {
                    $model->scienceDegrees()->createMany($scienceDegreesData);
                }
            }
        });
    }
}
