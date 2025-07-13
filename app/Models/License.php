<?php

namespace App\Models;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @mixin IdeHelperLicense
 */
class License extends Model
{
    use HasCamelCasing;

    protected $fillable = [
        'uuid',
        'legal_entity_id',
        'type',
        'issued_by',
        'issued_date',
        'active_from_date',
        'order_no',
        'license_number',
        'expiry_date',
        'what_licensed',
        'is_primary',
        'ehealth_inserted_at',
        'ehealth_inserted_by',
        'ehealth_updated_at',
        'ehealth_updated_by',
    ];

    protected $casts = [
        'uuid' => 'string',
        'legal_entity_id',
        'type' => 'string',
        'issued_by' => 'string',
        'issued_date' => 'datetime:Y-m-d',
        'active_from_date' => 'datetime:Y-m-d',
        'order_no' => 'string',
        'license_number' => 'string',
        'expiry_date' => 'datetime:Y-m-d',
        'what_licensed' => 'string',
        'is_primary' => 'boolean',
        'ehealth_inserted_at' => 'datetime',
        'ehealth_inserted_by' => 'string',
        'ehealth_updated_at' => 'datetime',
        'ehealth_updated_by' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'legal_entity_id', 'legal_entity_id');
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class, 'legal_entity_id', 'id');
    }
}
