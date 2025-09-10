<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QualificationRepository
{
    /**
     *
     * @param Model      $model
     * @param array|null $qualificationsData
     *
     * @return void
     * @throws \Throwable
     */
    public function syncQualifications(Model $model, ?array $qualificationsData): void
    {
        $qualificationsData = $qualificationsData ?? [];

        DB::transaction(function () use ($model, $qualificationsData) {
            $model->qualifications()->delete();

            if (!empty($qualificationsData)) {
                $model->qualifications()->createMany($qualificationsData);
            }
        });
    }
}
