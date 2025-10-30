<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use App\Models\MedicalEvents\Sql\CodeableConcept;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthcareService extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'uuid',
        'speciality_type',
        'providing_condition',
        'license_id',
        'division_id',
        'category_id',
        'type_id',
        'comment',
        'coverage_area',
        'available_time',
        'not_available',
        'status',
        'ehealth_inserted_at',
        'ehealth_inserted_by',
        'is_active',
        'legal_entity_id',
        'licensed_healthcare_service',
        'ehealth_updated_at',
        'ehealth_updated_by'
    ];

    protected $casts = [
        'available_time' => 'json',
        'not_available' => 'json',
        'status' => Status::class,
        'ehealth_inserted_at' => 'datetime',
        'created_at' => 'datetime'
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'category_id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'type_id');
    }

    /**
     * List of healthcare services for the current legal entity.
     *
     * @param  Builder  $query
     * @return Builder
     */
    #[Scope]
    protected function forLegalEntity(Builder $query): Builder
    {
        return $query->where('legal_entity_id', legalEntity()->id)
            ->select([
                'id',
                'uuid',
                'legal_entity_id',
                'division_id',
                'speciality_type',
                'providing_condition',
                'ehealth_inserted_at',
                'status',
                'created_at'
            ])
            ->with('division:id,name,status')
            ->orderByDesc('ehealth_inserted_at')
            ->orderByDesc('created_at');
    }
}
