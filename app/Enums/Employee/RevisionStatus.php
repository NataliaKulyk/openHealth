<?php

namespace App\Enums\Employee;

enum RevisionStatus: string
{
    case PENDING = 'PENDING';
    case APPLIED = 'APPLIED';
    case OUTDATED = 'OUTDATED';
    case SENT = 'SENT';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Очікує',
            self::APPLIED => 'Застосовано',
            self::OUTDATED => 'Застаріла',
            self::SENT => 'Відправлено в ЕСОЗ',
        };
    }
}
