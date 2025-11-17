<?php

declare(strict_types=1);

namespace App\Jobs\Traits;

use App\Classes\eHealth\EHealth;
use App\Models\Employee\EmployeeRequest;
use App\Repositories\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Throwable;

trait AppliesEmployeeRequestChanges
{
    /**
     * Applies the data from a specific EmployeeRequest's revision
     * to its associated Employee model.
     *
     * @param  EmployeeRequest  $request
     * @return bool
     * @throws Throwable
     */
    protected function applyChangesFromRevision(EmployeeRequest $request): bool
    {
        $employeeToUpdate = $request->employee;
        $revisionData = $request->revision?->data;

        if (!$employeeToUpdate || !$revisionData) {
            Log::warning('[' . __TRAIT__ . '] Skipping update: missing employee or revision data.', ['request_id' => $request->id]);

            return false;
        }

        $mappedData = EHealth::employeeRequest()->mapCreate($revisionData);

        Repository::employee()->updateDetails(
            $employeeToUpdate,
            $mappedData['party'],
            $mappedData['documents'],
            $mappedData['phones'],
            $mappedData['educations'] ?? null,
            $mappedData['specialities'] ?? null,
            $mappedData['qualifications'] ?? null,
            $mappedData['science_degree'] ?? null
        );
        $employeeToUpdate->update(Arr::get($mappedData, 'employee', []));

        Log::info('[' . __TRAIT__ . '] Successfully applied changes for employee ID: ' . $employeeToUpdate->id);

        return true;
    }
}
