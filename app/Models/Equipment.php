<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Equipment\AvailabilityStatus;
use App\Enums\Equipment\Status;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Equipment extends Model
{
    use HasCamelCasing;

    protected $table = 'equipments';

    protected $fillable = [
        'uuid',
        'legal_entity_id',
        'division_id',
        'parent_id',
        'recorder',
//        'device_definition_id',
        'type',
        'serial_number',
        'status',
        'availability_status',
        'manufacturer',
        'manufacture_date',
        'model_number',
        'inventory_number',
        'lot_number',
        'expiration_date',
        'note',
        'error_reason',
        'ehealth_inserted_at',
        'ehealth_inserted_by',
        'ehealth_updated_at',
        'ehealth_updated_by'
    ];

    protected $hidden = ['id'];

    protected $casts = [
        'status' => Status::class,
        'availability_status' => AvailabilityStatus::class,
    ];

    public function names(): HasMany
    {
        return $this->hasMany(EquipmentName::class, 'equipment_id');
    }

    #[Scope]
    public function active(Builder $query): Builder
    {
        return $query->whereStatus(Status::ACTIVE);
    }
}
