<?php

namespace App\Rules;

use Closure;

use App\Exceptions\CustomValidationException;
use Illuminate\Contracts\Validation\ValidationRule;

class PhoneDublicates implements ValidationRule
{
    protected $phones;

    public function __construct($phones = [])
    {
        $this->phones = $phones;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($this->phones as $phoneValue) {
            $type = $phoneValue['type'];

            $typeCount = array_reduce($this->phones, fn($sum, $item) => $sum = $item['type'] === $phoneValue['type'] ? $sum + 1 : $sum, 0);

            if ($typeCount > 1) {
                $typeName = dictionary()->getDictionary('PHONE_TYPE')[$type];

                $fail(__('validation.phone.dublicates', ['type' => $typeName]));
            }
        }
    }
}
