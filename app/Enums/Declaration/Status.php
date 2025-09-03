<?php

declare(strict_types=1);

namespace App\Enums\Declaration;

enum Status: string
{
    case DRAFT = 'DRAFT';
    case NEW = 'NEW';
    case APPROVED = 'APPROVED';
    case SIGNED = 'SIGNED';
    case ACTIVE = 'active';
    case TERMINATED = 'terminated';
    case REJECTED = 'REJECTED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Чернетка',
            self::NEW => 'Нова',
            self::APPROVED => 'Не підписана',
            self::SIGNED => 'Підписана',
            self::ACTIVE => 'Активна',
            self::REJECTED => 'Відхилена',
            self::CANCELLED => 'Відмінена'
        };
    }
}
