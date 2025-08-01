<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TaxId implements ValidationRule, DataAwareRule
{
    /**
     * All the data under validation.
     * @var array
     */
    protected array $data = [];

    public function __construct()
    {

    }

    /**
     * Set the data under validation.
     * @param  array  $data
     * @return $this
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $noTaxId = $this->data['party']['noTaxId'] ?? false;
        $email = $this->data['party']['email'] ?? null;

        if ($noTaxId) {
            if (!preg_match('/^([0-9]{9}|[А-ЯЁЇIЄҐ]{2}\\d{6})$/u', $value)) {
                $fail(__('validation.attributes.errors.invalidNationalId'));
            }
        } else {
            if (!preg_match('/^[0-9]{10}$/', $value)) {
                $fail(__('validation.attributes.errors.invalidTaxId'));
            }

            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user?->party && $value !== $user->party->taxId) {
                    $fail(__('validation.employee.wrong_tax_id'));
                }
                if ($user?->party && $user->party->taxId && !$value) {
                    $fail(__('validation.employee.missed_tax_id'));
                }
            }
        }
    }
}
