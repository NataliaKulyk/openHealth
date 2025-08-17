<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\Division\Location;
use App\Casts\Division\WorkingHours;
use App\Enums\Status;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Relations\Address;
use App\Models\Relations\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin IdeHelperDivision
 */
class Division extends Model
{
    public const string TYPE_FAP = 'FAP';
    public const string TYPE_CLINIC = 'CLINIC';
    public const string TYPE_AMBULANT_CLINIC = 'AMBULANT_CLINIC';

    protected $fillable = [
        'uuid',
        'external_id',
        'name',
        'type',
        'mountain_group',
        'location',
        'email',
        'working_hours',
        'is_active',
        'legal_entity_id',
        'status',
        'healthcare_services'
    ];

    protected $casts = [
        'location' => Location::class,
        'healthcare_services' => 'array',
        'working_hours' => WorkingHours::class,
        'is_active' => 'boolean',
        'status' => Status::class
    ];

    protected $attributes = [
        'is_active' => false,
        'mountain_group' => false,
        'uuid' => 'string'
    ];

    /**
     * Returns an array of available divison types.
     *
     * @return array
     */
    public static function getValidDivisionTypes(): array
    {
        return [
            self::TYPE_CLINIC,
            self::TYPE_AMBULANT_CLINIC,
            self::TYPE_FAP
        ];
    }

    /**
     * Returns an array of available LegalEntity types.
     *
     * @return array
     */
    public static function getValidLegalEntityTypes(): array
    {
        return [
            LegalEntity::TYPE_PRIMARY_CARE,
            LegalEntity::TYPE_MSP,
            LegalEntity::TYPE_MSP_PHARMACY
        ];
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function employeeRequests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class);
    }

    public function healthcareService(): HasMany
    {
        return $this->hasMany(HealthcareService::class);
    }

    public function address(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable');
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }
}
