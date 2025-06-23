<?php

namespace App\Rules;

use App\Models\Relations\Party;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueEmailInLegalEntity implements ValidationRule
{
    /**
     * The ID of the party to ignore during the uniqueness check (used for editing).
     */
    protected ?int $ignorePartyId;

    /**
     * Create a new rule instance.
     *
     * @param int|null $ignorePartyId The ID of the party to ignore.
     */
    public function __construct(?int $ignorePartyId = null)
    {
        $this->ignorePartyId = $ignorePartyId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $query = Party::where('email', $value)
            ->whereHas('employees', function ($query) {
                $query->where('legal_entity_id', legalEntity()->id);
            });

        if ($this->ignorePartyId) {
            $query->where('id', '!=', $this->ignorePartyId);
        }

        if ($query->exists()) {
            $fail(__('validation.unique', ['attribute' => 'email']));
        }
    }
}
