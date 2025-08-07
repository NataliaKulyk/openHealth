<?php

namespace App\Rules;

use Closure;
use App\Models\User;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class TaxId implements ValidationRule, DataAwareRule
{
    /**
     * All the data under validation.
     * @var array
     */
    protected array $data = [];

    protected bool $noTaxId;

    protected ?string $email;

    public function __construct()
    {
    }

    /**
     * Set the data under validation.
     * @param  array  $data
     *
     * @return $this
     */
    public function setData(array $data): static
    {
        // Employee Part
        if (!empty($data['party'])) {
            $this->noTaxId = $this->data['party']['noTaxId'] ?? false;
            $this->email = $this->data['party']['email'] ?? null;
        }

        // Legal Entity part
        if (!empty($data['owner'])) {
            $this->noTaxId = $this->data['owner']['noTaxId'] ?? false;
            $this->email = $this->data['owner']['email'] ?? '';
        }

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->noTaxId) {
            if (!preg_match('/^([0-9]{9}|[А-ЯЁЇIЄҐ]{2}\\d{6})$/u', $value)) {
                $fail(__('validation.attributes.errors.invalidNationalId'));
            }
        } else {
            if (!preg_match('/^[0-9]{10}$/', $value)) {
                $fail(__('validation.attributes.errors.invalidTaxId'));
            }

            if ($this->email) {
               $user = User::where('email', $this->email)->first();

                /*
                * Check that OWNER's tax_id from request is equal to party tax_id for OWNER's employee_id
                * see: https://e-health-ua.atlassian.net/wiki/spaces/EH/pages/583403638/Create+Update+Legal+Entity+V2
                */
                if ($user?->party && $value !== $user->party->taxId) {
                    $fail(__('validation.employee.wrong_tax_id'));
                }

                /*
                * Check that OWNER's tax_id from request is equal to party tax_id for OWNER's employee_id
                * see: https://e-health-ua.atlassian.net/wiki/spaces/EH/pages/583403638/Create+Update+Legal+Entity+V2
                */
                if ($user?->party && $user->party->taxId && !$value) {
                    $fail(__('validation.employee.missed_tax_id'));
                }
            }
        }
    }
}
