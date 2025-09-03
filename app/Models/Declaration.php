<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Declaration\Status;
use App\Models\Employee\Employee;
use App\Models\Person\Person;
use App\Traits\FormTrait;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperDeclaration
 */
class Declaration extends Model
{
    use FormTrait;
    use HasCamelCasing;

    protected $fillable = [
        'id',
        'uuid',
        'declaration_number',
        'declaration_request_id',
        'division_id',
        'employee_id',
        'legal_entity_id',
        'person_id',
        'end_date',
        'inserted_at',
        'is_active',
        'reason',
        'reason_description',
        'signed_at',
        'start_date',
        'status'
    ];

    protected $casts = [
        'status' => Status::class
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function legalEntity(): BelongsTo
    {
        return $this->belongsTo(LegalEntity::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
