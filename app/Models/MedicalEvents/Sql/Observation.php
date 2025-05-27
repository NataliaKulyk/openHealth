<?php

declare(strict_types=1);

namespace App\Models\MedicalEvents\Sql;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin IdeHelperObservation
 */
class Observation extends Model
{
    use HasCamelCasing;

    protected $guarded = [];

    protected $hidden = [
        'id',
        'encounter_id',
        'diagnostic_report_id',
        'code_id',
        'performer_id',
        'report_origin_id',
        'interpretation_id',
        'value_quantity_id',
        'value_codeable_concept_id',
        'body_site_id',
        'method_id',
        'reaction_on_id',
        'context_id',
        'created_at',
        'updated_at'
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function diagnosticReport(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'diagnostic_report_id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(CodeableConcept::class, 'observation_categories')->withTimestamps();
    }

    public function code(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'code_id');
    }

    public function effectivePeriod(): MorphOne
    {
        return $this->morphOne(Period::class, 'periodable');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'performer_id');
    }

    public function reportOrigin(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'report_origin_id');
    }

    public function interpretation(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'interpretation_id');
    }

    public function bodySite(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'body_site_id');
    }

    public function method(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'method_id');
    }

    public function valueQuantity(): BelongsTo
    {
        return $this->belongsTo(Quantity::class, 'value_quantity_id');
    }

    public function valueCodeableConcept(): BelongsTo
    {
        return $this->belongsTo(CodeableConcept::class, 'value_codeable_concept_id');
    }

    public function reactionOn(): BelongsTo
    {
        return $this->belongsTo(Identifier::class, 'reaction_on_id');
    }

    public function components(): HasMany
    {
        return $this->hasMany(ObservationComponent::class);
    }
}
