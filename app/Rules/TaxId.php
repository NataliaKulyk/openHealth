<?php

namespace App\Rules;

use Closure;
use App\Models\User;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class TaxId implements ValidationRule, DataAwareRule
{
    /**
     * The entire data array under validation.
     * @var array
     */
    protected array $data = [];

    /**
     * Flag indicating if the ID is a passport/national ID instead of a tax ID.
     * @var bool
     */
    protected bool $noTaxId = false;

    /**
     * The email associated with the person, used for additional checks.
     * @var string|null
     */
    protected ?string $email = null;

    /**
     * Set the data under validation and determine the context.
     *
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        $contextData = null;

        if (!empty($data['party']) && is_array($data['party'])) {
            $contextData = $data['party'];
        }

        elseif (!empty($data['owner']) && is_array($data['owner'])) {
            $contextData = $data['owner'];
        }

        if ($contextData) {
            $this->noTaxId = (bool)($contextData['noTaxId'] ?? false);
            $this->email = $contextData['email'] ?? null;
        }

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->noTaxId) {

            if (!preg_match('/^([0-9]{9}|[А-ЯЁЇIЄҐ]{2}\d{6})$/u', $value)) {
                $fail(__('validation.attributes.errors.invalidNationalId'));
            }
        }

        else {
            // Стандартний ІПН (10 цифр).
            if (!preg_match('/^[0-9]{10}$/', $value)) {
                $fail(__('validation.attributes.errors.invalidTaxId'));
            }

            if ($this->email) {
                $user = User::where('email', $this->email)->first();

                if ($user?->party && $value !== $user->party->taxId) {
                    $fail(__('validation.employee.wrong_tax_id'));
                }

                if ($user?->party && $user?->party->taxId && !$value) {
                    $fail(__('validation.employee.missed_tax_id'));
                }
            }
        }
    }
}
