<?php

namespace App\Exceptions\EHealth;

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
}
