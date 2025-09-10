<?php

namespace App\Enums\Employee;

enum RequestStatus: string
{
    case NEW = 'NEW';
    case APPROVED = 'APPROVED';
    case REJECTED = 'REJECTED';
    case SIGNED = 'SIGNED';
    case DISMISSED = 'DISMISSED';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Новий',
            self::APPROVED => 'Підтверджено',
            self::REJECTED => 'Відхилено',
            self::SIGNED => 'Підписано в ЕСОЗ',
            self::DISMISSED => 'Звільнено',
        };
    }

    /**
     * Returns an array of statuses pending
     * final synchronization upon user login.
     */
    public static function getStatusesForSync(): array
    {
        return [
            self::NEW->value,
            self::SIGNED->value,
        ];
    }
}
