<?php

declare(strict_types=1);

namespace App\Exceptions\EHealth;

use App\Core\Arr as AppArr;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

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
     * Get the translated error message based on eHealth details.
     * Uses existing translation files for attributes and messages.
     */
    public function getTranslatedMessage(): string
    {
        $invalidErrors = Arr::get($this->details, 'error.invalid') ?? Arr::get($this->details, 'invalid') ?? [];

        $errorList = collect($invalidErrors)->map(function ($detail) {
            $eHealthKey = AppArr::get($detail, 'entry') ?? AppArr::get($detail, 'param') ?? 'unknown';
            $message = AppArr::get($detail, 'rules.0.description') ?? AppArr::get($detail, 'msg') ?? '';
            $ruleName = AppArr::get($detail, 'rules.0.rule');

            if ($eHealthKey === 'status') {
                return null;
            }

            $eHealthKey = str_replace(['$.', 'employee_request.'], '', $eHealthKey);

            $translatedKey = trans('validation.attributes.' . $eHealthKey, [], 'uk');
            if ($translatedKey === 'validation.attributes.' . $eHealthKey) {
                $translatedKey = trans('forms.' . $eHealthKey, [], 'uk');
            }

            $translatedMessage = $this->findTranslationForMessage($message, $ruleName);

            return "{$translatedKey}: {$translatedMessage}";
        })->filter()->implode("\n");

        return trans('errors.ehealth.validation_error_header') . "\n" . $errorList;
    }

    /**
     * Finds the most appropriate translation for a given eHealth error message.
     */
    private function findTranslationForMessage(string $message, ?string $ruleName): string
    {

        if (str_contains($message, 'speciality') && str_contains($message, ' with active speciality_officio is not allowed for doctor')) {
            preg_match('/speciality (.+?) with active speciality_officio is not allowed for doctor/', $message, $matches);
            return trans('errors.ehealth.messages.speciality_officio_not_allowed', ['speciality' => $matches[1] ?? '']);
        }

        $key = $ruleName ?? $message;
        if (trans()->has('errors.ehealth.messages.' . $key)) {
            return trans('errors.ehealth.messages.' . $key);
        }

        Log::warning("Untranslated eHealth error message found.", [
            'original_message' => $message,
            'rule_name' => $ruleName,
        ]);

        return $message;
    }
}
