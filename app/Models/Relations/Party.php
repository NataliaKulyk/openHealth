<?php

namespace App\Models\Relations;

use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use Eloquence\Behaviours\HasCamelCasing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Party extends Model
{
    use HasCamelCasing;
    use HasFactory;

    protected $fillable = [
        'uuid',
        'last_name',
        'first_name',
        'second_name',
        'email',
        'birth_date',
        'gender',
        'tax_id',
        'no_tax_id',
        'about_myself',
        'working_experience',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public $timestamps = false;

    /**
     * Get the party's full name.
     * This is an accessor, allowing you to use it like a property: $party->fullName
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        $fullName = trim($this->last_name . ' ' . $this->first_name);

        if (!empty($this->second_name)) {
            $fullName .= ' ' . $this->second_name;
        }

        return $fullName;
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'party_id');
    }

    public function employeeRequests(): HasMany
    {
        return $this->hasMany(EmployeeRequest::class, 'party_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function phones(): MorphMany
    {
        return $this->morphMany(Phone::class, 'phoneable');
    }
}
