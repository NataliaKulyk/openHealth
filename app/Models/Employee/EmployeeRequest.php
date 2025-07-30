<?php

namespace App\Models\Employee;

use App\Enums\Employee\RequestStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperEmployeeRequest
 */
class EmployeeRequest extends BaseEmployee
{
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->with = array_merge($this->with, ['revision', 'employee']);
        $this->fillable = array_merge($this->fillable, ['applied_at']);
        $this->casts = array_merge($this->casts, [
            'applied_at' => 'datetime',
        ]);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    protected $casts = [
        'status' => RequestStatus::class,
    ];
}
