<?php

declare(strict_types=1);

namespace App\Models\Person;

use App\Enums\Person\Status;
use App\Models\Relations\ConfidantPerson;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PersonRequest extends BasePerson
{
    public function __construct()
    {
        parent::__construct();
        $this->mergeFillable(['status', 'person_id', 'authorize_with']);
        $this->mergeCasts(['status' => Status::class]);
    }

    protected static function boot(): void
    {
        parent::boot();

        // Cascade delete
        static::deleting(static function (PersonRequest $personRequest) {
            if ($personRequest->confidantPerson) {
                $personRequest->confidantPerson->documentsRelationship()->delete();
                $personRequest->confidantPerson->delete();
            }

            $personRequest->addresses()->delete();
            $personRequest->documents()->delete();
            $personRequest->phones()->delete();
            $personRequest->authenticationMethods()->delete();
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function confidantPerson(): HasOne
    {
        return $this->hasOne(ConfidantPerson::class);
    }
}
