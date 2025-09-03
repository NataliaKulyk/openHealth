<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Declaration\Status;
use App\Models\Employee\Employee;
use App\Models\Person\Person;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperDeclarationRequest
 */
class DeclarationRequest extends Model
{
    use HasCamelCasing;

    protected $guarded = [];

    protected $casts = [
        'status' => Status::class,
        'data_to_be_signed' => 'array'
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
