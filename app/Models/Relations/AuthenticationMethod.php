<?php

declare(strict_types=1);

namespace App\Models\Relations;

use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuthenticationMethod extends Model
{
    use HasCamelCasing;

    protected $hidden = [
        'id',
        'uuid',
        'authenticatable_type',
        'authenticatable_id',
        'ehealth_ended_at',
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'uuid',
        'authenticatable_type',
        'authenticatable_id',
        'type',
        'phone_number',
        'value',
        'alias',
        'ehealth_ended_at'
    ];

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }
}
