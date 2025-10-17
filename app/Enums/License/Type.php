<?php

declare(strict_types=1);

namespace App\Enums\License;

use App\Traits\EnumUtils;

enum Type: string
{
    use EnumUtils;

    case MSP = 'MSP';
    case PHARMACY = 'PHARMACY';
    case PHARMACY_DRUGS = 'PHARMACY_DRUGS';

    public function label(): string
    {
        return match ($this) {
            self::MSP => __('licenses.status.msp'),
            self::PHARMACY => __('licenses.status.pharmacy'),
            self::PHARMACY_DRUGS => __('licenses.status.pharmacy_drugs')
        };
    }
}
