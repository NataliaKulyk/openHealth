<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use App\Models\Employee\Employee;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeRole extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'uuid',
        'employee_id',
        'healthcare_service_id',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'ehealth_inserted_at',
        'ehealth_inserted_by',
        'ehealth_updated_at',
        'ehealth_updated_by'
    ];

    protected $hidden = ['id'];

    protected $casts = ['status' => Status::class];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function healthcareService(): BelongsTo
    {
        return $this->belongsTo(HealthcareService::class);
    }
}
