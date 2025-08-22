<?php

namespace App\Exceptions\EHealth;

class EHealthValidationException extends EHealthException
{
    public function __construct(public readonly array $details)
    {
        parent::__construct('eHealth API returned a validation error.');
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * Gets a translated version of the eHealth validation error message.
     */
    public function getTranslatedMessage(): string
    {
        $eHealthFieldTranslations = [
            'party.first_name' => __('forms.first_name'),
            'party.last_name' => __('forms.last_name'),
            'party.second_name' => __('forms.second_name'),
            'party.birth_date' => __('forms.birth_date'),
            'party.tax_id' => __('forms.tax_id'),
            'doctor' => __('forms.doctor_data'),
            'start_date' => __('forms.start_date_work'),
            'employee_type' => __('forms.role'),
            'forms.doctor_data' => __('forms.doctor_data'),
            'party' => __('forms.personal_data'),
            'position' => __('forms.position'),
            'employee_request' => __('forms.employee_requests'),
            'doctor.science_degree' => __('forms.science_degree'),
            'party.documents.[0].number' => __('forms.document_number'),
            'doctor.qualifications' => __('forms.qualifications'),
            'doctor.specialities' => __('forms.specialities'),
            // Add more translations as needed
        ];

        $eHealthMessageTranslations = __('errors.ehealth.messages');

        $errorList = collect($this->getDetails())->map(function ($detail) use ($eHealthFieldTranslations, $eHealthMessageTranslations) {
            $eHealthKey = $detail['entry'] ?? ($detail['param'] ?? 'unknown');

            // Workaround for a bug in eHealth API where it returns a validation error for 'status'
            if ($eHealthKey === 'status') {
                return null;
            }

            $eHealthKey = str_replace(['$.', 'employee_request.'], '', $eHealthKey);
            $message = $detail['rules'][0]['description'] ?? ($detail['msg'] ?? 'Incorrect value.');

            // Translate the eHealth key using the map.
            $translatedKey = $eHealthFieldTranslations[$eHealthKey] ?? $eHealthKey;

            // Find and apply a translated message from our new map
            $translatedMessage = 'Некоректне значення.'; // Default message
            foreach ($eHealthMessageTranslations as $key => $translation) {
                if (str_contains($message, $key)) {
                    $translatedMessage = $translation;
                    break;
                }
            }

            return "{$translatedKey}: {$translatedMessage}";
        })->filter()->implode("\n");

        $header = __('errors.ehealth.validation_error_header');

        return "{$header}\n{$errorList}";
    }
}
