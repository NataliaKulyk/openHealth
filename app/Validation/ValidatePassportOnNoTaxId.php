<?php

namespace App\Validation;

use Illuminate\Validation\Validator;

final class ValidatePassportOnNoTaxId
{
    /**
     * Handle the custom validation logic.
     *
     * @param Validator $validator
     * @return void
     */
    public function __invoke(Validator $validator): void
    {
        $data = $validator->getData();
        $noTaxId = data_get($data, 'form.party.noTaxId', false);

        if ($noTaxId) {
            $documents = data_get($data, 'form.documents', []);
            $passport = collect($documents)->firstWhere('type', 'PASSPORT');

            if (!$passport) {
                $validator->errors()->add(
                    'form.documents',
                    __('validation.custom.passport_required_if_no_tax_id')
                );
                return;
            }

            $passportNumber = $passport['number'] ?? '';
            if (!preg_match('/^((?![ЫЪЭЁ])([А-ЯҐЇІЄ])){2}[0-9]{6}$/u', $passportNumber)) {
                $passportIndex = collect($documents)->search(fn ($doc) => $doc['type'] === 'PASSPORT');
                $validator->errors()->add(
                    "form.documents.{$passportIndex}.number",
                    'Номер паспорта має відповідати формату: 2 великі українські літери, 6 цифр.'
                );
            }
        }
    }
}
