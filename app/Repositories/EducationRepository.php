<?php

namespace App\Repositories;

use App\Models\Employee\Employee;
use App\Models\Relations\Education;
use DB;

class EducationRepository
{
    // Перейменуй `addEducations` на `syncEducations` для консистентності
    public function syncEducations(Employee $employee, ?array $educationsData): void
    {
        $educationsData = $educationsData ?? [];

        DB::transaction(function () use ($employee, $educationsData) {
            $employee->educations()->delete();
            if (!empty($educationsData)) {
                $employee->educations()->createMany($educationsData);
            }
        });
    }
}
