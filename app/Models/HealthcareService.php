<?php

namespace App\Models;

use \App\Enums\Status;
use App\Casts\NotAvailableTimeCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperHealthcareService
 */
class HealthcareService extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'speciality_type',
        'providing_condition',
        'license_id',
        'division_id',
        'category',
        'type',
        'comment',
        'coverage_area',
        'available_time',
        'not_available',
        'status',
        'ehealth_inserted_at',
        'ehealth_inserted_by',
        'is_active',
        'legal_entity_uuid',
        'licensed_healthcare_service',
        'ehealth_updated_at',
        'ehealth_updated_by'
    ];

    protected $casts = [
        'category' => 'json',
        'type' => 'json',
        'coverage_area' => 'json',
        'available_time' => 'json',
        'not_available' => NotAvailableTimeCast::class,
        'status' => Status::class
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function getHealthcareCategoryAttribute()
    {
        return $this->category['coding'][0]['code'] ?? '';
    }
}
