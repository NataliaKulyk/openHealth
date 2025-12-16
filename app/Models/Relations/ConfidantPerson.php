<?php

declare(strict_types=1);

namespace App\Models\Relations;

use App\Models\Person\{Person, PersonRequest};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ConfidantPerson extends Model
{
    protected $table = 'confidant_persons';

    protected $hidden = [
        'id',
        'person_request_id',
        'subject_person_id',
        'created_at',
        'updated_at'
    ];

    protected $fillable = [
        'person_request_id',
        'person_id',
        'subject_person_id'
    ];

    /**
     * Act as confidant for another person.
     *
     * @return BelongsTo
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /**
     * Person who need confidant person (young or incapacitated).
     *
     * @return BelongsTo
     */
    public function subjectPerson(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'subject_person_id');
    }

    public function personRequest(): BelongsTo
    {
        return $this->belongsTo(PersonRequest::class);
    }

    public function documentsRelationship(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
