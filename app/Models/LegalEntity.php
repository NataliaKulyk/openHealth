<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Status;
use App\Models\Relations\Phone;
use App\Models\Relations\Address;
use App\Models\Employee\Employee;
use App\Models\Division;
use App\Models\Contract;
use App\Models\License;
use App\Models\Revision;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;
use App\Casts\LegalEntityArchiveCast;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employee\EmployeeRequest;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Builder;
use App\Casts\LegalEntityAccreditationCast;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperLegalEntity
 */
class LegalEntity extends Model
{
    use HasCamelCasing;

    public const string TYPE_MSP = 'MSP';
    public const string TYPE_MIS = 'MIS';
    public const string TYPE_NHS = 'NHS';
    public const string TYPE_PHARMACY = 'PHARMACY';
    public const string TYPE_EMERGENCY = 'EMERGENCY';
    public const string TYPE_OUTPATIENT = 'OUTPATIENT';
    public const string TYPE_PRIMARY_CARE = 'PRIMARY_CARE';
    public const string TYPE_MSP_PHARMACY = 'MSP_PHARMACY';

    protected $fillable = [
        'uuid',
        'accreditation',
        'archive',
        'beneficiary',
        'edr',
        'edr_verified',
        'edrpou',
        'email',
        'inserted_at',
        'inserted_by',
        'is_active',
        'nhs_comment',
        'nhs_reviewed',
        'nhs_verified',
        'receiver_funds_code',
        'residence_address',
        'status',
        'type',
        'updated_at',
        'updated_by',
        'website',
        'client_id',
        'client_secret',
    ];

    protected $casts = [
        'accreditation' => LegalEntityAccreditationCast::class,
        'archive' => LegalEntityArchiveCast::class,
        'edr' => 'array',
        'inserted_at' => 'datetime',
        'updated_at' => 'datetime',
        'id' => 'string',
        'inserted_by' => 'string',
        'updated_by' => 'string',
    ];

    protected $attributes = [
        'is_active' => false,
    ];

    public null|object $owner;

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function employeeRequests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class);
    }

    public function setAddressesAttribute($value)
    {
        $this->attributes['addresses'] = json_encode($value);
    }

    public function setKvedsAttribute($value)
    {
        $this->attributes['kveds'] = json_encode($value);
    }

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    public function contract(): HasMany
    {
        return $this->hasMany(Contract::class, 'legal_entity_id', 'id');
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    // Get Legal Entity UUID
    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    // Get Owner Legal Entity
    public function getOwner(): ?object
    {
        return $this->employees()->where('employee_type', 'OWNER')->first();
    }

    public function getActiveDivisions(): Collection
    {
        return $this->divisions()->has('healthcareService')->where('status', Status::ACTIVE)->get();
    }

    public function getEdr(): array
    {
        return $this->edr;
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }

    public function revisions(): MorphMany
    {
        return $this->morphMany(Revision::class, 'revisionable');
    }

    /**
     * Scope a query to get an Legal Entity depends on it's UUID
     */
    public function scopeByUuid(Builder $query, string $legalEntityUUID): void
    {
        $query->where('uuid', $legalEntityUUID);
    }
}
