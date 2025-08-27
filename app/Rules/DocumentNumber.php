<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;


class DocumentNumber implements ValidationRule
{
    protected array $dictionary;

    protected string $documentType;

    /**
     * Create a new rule instance.
     *
     * @param  string  $documentType
     *
     * @return void
     */
    public function __construct(string $documentType = '')
    {
        $this->documentType = $documentType;

        $this->dictionary = dictionary()->getDictionary('DOCUMENT_TYPE', true);
    }

    /**
     * Перевіряє, чи номер документа відповідає формату його типу.
     *
     * @param  \Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $regex = match ($this->documentType) {
            'NATIONAL_ID' => '/^[0-9]{9}$/',
            'PASSPORT', 'REFUGEE_CERTIFICATE', 'COMPLEMENTARY_PROTECTION_CERTIFICATE' => '/^((?![ЫЪЭЁ])([А-ЯҐЇІЄ])){2}[0-9]{6}$/u',
            'TEMPORARY_CERTIFICATE', 'PERMANENT_RESIDENCE_PERMIT' => '/^(((?![ЫЪЭЁ])([А-ЯҐЇІЄ])){2}[0-9]{4,6}|[0-9]{9}|((?![ЫЪЭЁ])([А-ЯҐЇІЄ])){2}[0-9]{5}\/[0-9]{5})$/u',
            'TEMPORARY_PASSPORT', 'BIRTH_CERTIFICATE', 'BIRTH_CERTIFICATE_FOREIGN' => '/^((?![ЫЪЭЁыъэё@%&$^#`~:,.*|}{?!])[A-ZА-ЯҐЇІЄ0-9№\\/()-]){2,25}$/u',
            default => ''
        };

        if (!$regex || !preg_match($regex, $value)) {
            $fail(__('forms.document') . ': ' . __('validation.attributes.errors.wrongNumberFormat'));
        }
    }
}
