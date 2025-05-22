<?php

namespace App\Models\Employee;

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
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
