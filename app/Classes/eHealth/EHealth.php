<?php

declare(strict_types=1);

namespace App\Classes\eHealth;

use App\Classes\eHealth\Api\License;

final class EHealth
{
    public static function license()
    {
        return app(License::class);
    }
}
