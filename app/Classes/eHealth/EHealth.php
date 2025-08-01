<?php

declare(strict_types=1);

namespace App\Classes\eHealth;

use App\Classes\eHealth\Api\License;
use App\Classes\eHealth\Api\Job;
use App\Classes\eHealth\Api\PersonRequest;

final class EHealth
{
    public static function license(): License
    {
        return app(License::class);
    }

    public static function job(): Job
    {
        return app(Job::class);
    }

    public static function personRequest(): PersonRequest
    {
        return app(PersonRequest::class);
    }
}
