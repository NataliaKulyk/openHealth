<?php

declare(strict_types=1);

namespace App\Enums\Declaration;

enum RequestStatus: string
{
    case DRAFT = 'DRAFT';
    case NEW = 'NEW';
    case APPROVED = 'APPROVED';
    case SIGNED = 'SIGNED';
    case CANCELLED = 'CANCELLED';
    case EXPIRED = 'EXPIRED';
    case REJECTED = 'REJECTED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Чернетка',
            self::NEW => 'Новий',
            self::APPROVED => 'Підтверджений',
            self::SIGNED => 'Підписаний',
            self::CANCELLED => 'Скасований',
            self::EXPIRED => 'Прострочений',
            self::REJECTED => 'Відхилений'
        };
    }
}
