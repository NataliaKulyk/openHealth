<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\EnumUtils;

enum Status: string
{
    use EnumUtils;

    case NEW = 'NEW';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case SIGNED = 'SIGNED';
    case DISMISSED = 'DISMISSED';
    case ACTIVE = 'ACTIVE';
    case INACTIVE = 'INACTIVE';
    case DRAFT = 'DRAFT';
    case UNSYNCED = 'UNSYNCED';

    public function label(): string
    {
        return match ($this) {
            self::NEW => __('forms.status.new'),
            self::APPROVED => __('forms.status.approved'),
            self::REJECTED => __('forms.status.rejected'),
            self::SIGNED => __('forms.status.signed'),
            self::DISMISSED => __('forms.status.dismissed'),
            self::ACTIVE => __('forms.status.active'),
            self::INACTIVE => __('forms.status.non_active'),
            self::DRAFT => __('forms.status.draft'),
            self::UNSYNCED => __('forms.status.unsynced'),
        };
    }

    public static function only(array $names): array
    {
        return collect(self::cases())
            ->filter(fn ($case) => in_array($case->name, $names, true))
            ->map(fn ($case) => $case->value)
            ->values()
            ->all();
    }
}
