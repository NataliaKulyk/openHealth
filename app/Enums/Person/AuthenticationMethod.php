<?php

declare(strict_types=1);

namespace App\Enums\Person;

enum AuthenticationMethod: string
{
    case OTP = 'OTP';
    case OFFLINE = 'OFFLINE';
    case THIRD_PERSON = 'THIRD_PERSON';

    public function label(): string
    {
        return match ($this) {
            self::OTP => __('patients.authentication_method.otp'),
            self::OFFLINE => __('patients.authentication_method.offline'),
            self::THIRD_PERSON => __('patients.authentication_method.third_person')
        };
    }

    public static function getOptions(): array
    {
        return array_map(static fn (self $case) => $case->label(), self::cases());
    }
}
