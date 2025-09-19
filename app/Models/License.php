<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\NullableDateCast;
use App\Enums\License\Type;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

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
        'is_active',
        'issued_by',
        'issued_date',
        'issuer_status',
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

    protected $hidden = [
        'id'
    ];

    protected $casts = [
        'type' => Type::class,
        'issued_date' => NullableDateCast::class,
        'active_from_date' => NullableDateCast::class,
        'expiry_date' => NullableDateCast::class,
        'ehealth_inserted_at' => 'datetime',
        'ehealth_inserted_by' => 'string',
        'ehealth_updated_at' => 'datetime',
        'ehealth_updated_by' => 'string',
    ];

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }
}
