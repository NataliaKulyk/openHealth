<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Support\Str;
use App\Models\Person\Person;
use App\Models\Employee\Employee;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Employee\EmployeeRequest;
use Spatie\Permission\Models\Permission;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @mixin IdeHelperUser
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles {
        getAllPermissions as getAllPermissionsTrait;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'email',
        'password',
        'secret_key'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['person'];

    /* This need to override because trait HasProfilePhoto was disabled to remove 'name' attribute calling */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->profile_photo_path
            ? asset('storage/' . $this->profile_photo_path)
            : $this->defaultProfilePhotoUrl();
    }

    /* This need to override because trait HasProfilePhoto was disabled to remove 'name' attribute calling */
    public function defaultProfilePhotoUrl(): string
    {
        return '';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\belongsTo
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /* Check if user has access to the Legal Entity with specified UUID */
    public function hasAccessToLegalEntityByUuid(string $legalEntityUuid): bool
    {
        return $this->employees()
                    ->whereHas('legalEntity', function($query) use($legalEntityUuid) {
                        $query->where('uuid', $legalEntityUuid);
                    })
                    ->exists();
    }

    /* Get ALL Legal Entites IDs available for this user */
    public function accessibleLegalEntities(): Collection
    {
        return $this->employees()
                    ->with('legalEntity')
                    ->get()
                    ->unique('legal_entity_id')
                    ->pluck('legal_entity_id');
    }

    // TODO: Check why need it for??????
    public function licenses(): HasMany
    {
        return $this->hasMany(License::class, 'legal_entity_id', 'legal_entity_id');
    }

    public function employeeRequests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    /**
     * Overides trait's method to exclude unused scopes
     * @return Collection<Permission> a list of scopes associated with the user and entity type
     */
    public function getAllPermissions(string $legalEntityClientId): Collection
    {
        $scopes = $this->getAllPermissionsTrait();

        $legalEntity = LegalEntity::where('client_id', $legalEntityClientId)->first();

        $exclude = []; // exclude scopes not used by the entity

        switch ($legalEntity->type) {
            case LegalEntity::TYPE_PRIMARY_CARE:
                $exclude = array_merge($exclude, ['contract_request:sign', 'contract_request:terminate', 'contract:write', 'contract_request:approve', 'contract_request:create']);
                break;
        }

        return $scopes->filter(
            fn (Permission $permission) =>
            !collect($exclude)->some(fn($excluded) => Str::startsWith($permission->name, $excluded))
        );
    }

    /**
     * Retrieves the scopes assigned to a specific user.
     *
     * @param User $user The user instance for which to retrieve scopes
     *
     * @return string The concatenated string of user's scopes
     */
    public function getScopes(string $legalEntityClientId): string
    {
        return $this->getAllPermissions($legalEntityClientId)->unique()->pluck('name')->join(' ');
    }

    /**
     * Get employee by priority with encounter:write permission.
     *
     * @return Employee|null
     */
    public function getEncounterWriterEmployee(): ?Employee
    {
        // Ordered role from most valuable to least with permission encounter:write
        $priorityRoles = ['DOCTOR', 'SPECIALIST', 'ASSISTANT', 'MED_COORDINATOR'];

        // Get first by roles priority
        return collect($priorityRoles)
            ->map(fn (string $type) => $this->employees->firstWhere('employee_type', $type))
            ->first();
    }

    /**
     * Get employee by priority with diagnostic_report:write permission.
     *
     * @return Employee|null
     */
    public function getDiagnosticReportWriterEmployee(): ?Employee
    {
        // Ordered role from most valuable to least with permission diagnostic_report:write
        $priorityRoles = ['DOCTOR', 'SPECIALIST', 'ASSISTANT', 'LABORANT'];

        // Get first by roles priority
        return collect($priorityRoles)
            ->map(fn (string $type) => $this->employees->firstWhere('employee_type', $type))
            ->first();
    }

    /**
     * Get email verified at timestamp in camelCase
     *
     * @return mixed
     */
    public function getEmailVerifiedAtAttribute()
    {
        return $this->attributes['email_verified_at'];
    }
}
