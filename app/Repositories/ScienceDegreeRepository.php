<?php

namespace App\Repositories;

use App\Models\Relations\ScienceDegree;
use Illuminate\Database\Eloquent\Model;

class ScienceDegreeRepository
{
    /**
     * @param Model $model The employee or employee request model (Employee or EmployeeRequest).
     * @param array $scienceDegreesData An array of science degree data arrays (e.g., [['degree' => 'PhD', ...], ['degree' => 'Master', ...]]).
     *
     * @return void
     */
    public function addScienceDegrees(Model $model, array $scienceDegreesData): void
    {
        $model->scienceDegrees()->delete();

        if (empty($scienceDegreesData)) {
            return;
        }

        foreach ($scienceDegreesData as $individualScienceDegreeData) {
            $scienceDegree = new ScienceDegree();

            $scienceDegree->fill($individualScienceDegreeData);

            $model->scienceDegrees()->save($scienceDegree);
        }
    }
}
