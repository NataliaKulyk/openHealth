<?php

namespace App\Models\Employee;

use App\Enums\Employee\RequestStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a request to create or modify an employee.
 * Inherits common properties from BaseEmployee.
 *
 * @mixin IdeHelperEmployeeRequest
 */
class EmployeeRequest extends BaseEmployee
{
    protected $table = 'employee_requests';

    /**
     * The attributes that are mass assignable.
     * Extends the list from the parent BaseEmployee class.
     */
    protected $fillable = [
        'uuid',
        'legal_entity_uuid',
        'division_uuid',
        'legal_entity_id',
        'status',
        'position',
        'start_date',
        'end_date',
        'party_id',
        'employee_type',
        'user_id',
        'division_id',
        'inserted_at',
        'applied_at',
        'employee_id'
    ];

    /**
     * The attributes that should be cast.
     * Extends the casts from the parent BaseEmployee class.
     */
    protected $casts = [
        'status'       => RequestStatus::class,
        'start_date'   => 'date:Y-m-d',
        'end_date'     => 'date:Y-m-d',
        'applied_at'   => 'datetime',
    ];

    // --- REQUEST-SPECIFIC RELATIONS ---

    /**
     * The employee this request is associated with (can be null for new employees).
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    // --- TEMPORARY SCOPES (to be removed after controller refactoring) ---

    public function scopeEmployeeInstance(Builder $query, int $userId, string $legalEntityUUID, array $roles, bool $isInclude = false): void
    {
        $query->where('user_id', $userId)
            ->where('legal_entity_uuid', $legalEntityUUID)
            ->when($isInclude,
                fn ($q) => $q->whereIn('employee_type', $roles),
                fn ($q) => $q->whereNotIn('employee_type', $roles)
            );
    }
}
