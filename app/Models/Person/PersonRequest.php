<?php

declare(strict_types=1);

namespace App\Models\Person;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPersonRequest
 */
class PersonRequest extends BasePerson
{
    public function __construct()
    {
        parent::__construct();
        $this->mergeFillable(['status', 'person_id', 'authorize_with']);
    }

    protected static function boot(): void
    {
        parent::boot();

        // Cascade delete
        static::deleting(static function (PersonRequest $personRequest) {
            $personRequest->address()->delete();
            $personRequest->documents()->delete();
            $personRequest->phones()->delete();
            $personRequest->authenticationMethod()->delete();
        });
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    #[Scope]
    protected function showPersonRequest(Builder $query, int $id): Builder
    {
        return $query->with(['phones', 'authenticationMethod', 'documents', 'address', 'confidantPerson'])
            ->where('id', $id);
    }
}
