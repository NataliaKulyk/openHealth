<?php

declare(strict_types=1);

namespace App\Models\Employee;

use App\Enums\JobStatus;
use App\Enums\Status;
use App\Models\Declaration;
use App\Models\Relations\Education;
use App\Models\Relations\Qualification;
use App\Models\Relations\ScienceDegree;
use App\Models\Relations\Speciality;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Employee extends BaseEmployee
{
    protected $table = 'employees';

    /**
     * Merging parent casts with specific ones for this model.
     */
    protected $casts = [
        'status' => Status::class,
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    // --- EMPLOYEE-SPECIFIC RELATIONS ---

    public function declarations(): HasMany
    {
        return $this->hasMany(Declaration::class);
    }

    public function educations(): MorphMany
    {
        return $this->morphMany(Education::class, 'educationable');
    }

    public function scienceDegree(): MorphOne
    {
        return $this->morphOne(ScienceDegree::class, 'science_degreeable');
    }

    public function qualifications(): MorphMany
    {
        return $this->morphMany(Qualification::class, 'qualificationable');
    }

    public function specialities(): MorphMany
    {
        return $this->morphMany(Speciality::class, 'specialityable');
    }

    public function setSyncStatus(JobStatus $status): void
    {
        $this->sync_status = $status;
        $this->save();
    }

    // --- EMPLOYEE-SPECIFIC SCOPES ---

    public function scopeDoctor(Builder $query): Builder
    {
        return $query->where('employee_type', 'DOCTOR');
    }

    public function scopeEmployeeInstance(Builder $query, int $userId, string $legalEntityUUID, array $roles, bool $isInclude = false): void
    {
        $query->where('user_id', $userId)
            ->where('legal_entity_uuid', $legalEntityUUID)
            ->when(
                $isInclude,
                fn ($q) => $q->whereIn('employee_type', $roles),
                fn ($q) => $q->whereNotIn('employee_type', $roles)
            );
    }

    public function scopeIdentifyEmployee(Builder $query, array $employeeTypes, string $status, int $userId, int $legalEntityId, ?int $partyId): void
    {
        $query->whereIn('employee_type', $employeeTypes)
            ->where('status', $status)
            ->where('user_id', $userId)
            ->where('legal_entity_id', $legalEntityId)
            ->where('party_id', $partyId);
    }

    public function scopeFilterByUuids(Builder $query, array $uuids): Builder
    {
        return $query->whereIn('uuid', $uuids);
    }

    public function scopeFilterBySyncStatus(Builder $query, JobStatus $status): Builder
    {
        return $query->where('sync_status', $status);
    }

    #[Scope]
    protected function activeSpecialists(Builder $query, int $legalEntityId): Builder
    {
        return $query->whereLegalEntityId($legalEntityId)
            ->whereStatus(Status::APPROVED)
            ->whereIsActive(true)
            ->whereHas('specialities', function (Builder $query) {
                $query->select('id')->whereSpecialityOfficio(true);
            })
            ->select(['id', 'uuid', 'party_id', 'position'])
            ->with('party:id,first_name,last_name,second_name');
    }

    #[Scope]
    protected function activeRecorders(Builder $query, int $legalEntityId): Builder
    {
        return $query->whereLegalEntityId($legalEntityId)
            ->whereStatus(Status::APPROVED)
            ->whereIsActive(true)
            ->whereHas(
                'party',
                fn (Builder $query) => $query->select('id')->whereNot('verification_status', '=', 'NOT_VERIFIED')
            )
            ->with('party:id,first_name,last_name,second_name');
    }
}
