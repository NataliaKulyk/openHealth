<?php

declare(strict_types=1);

namespace App\Core;

use App\Rules\SigningRules;
use Livewire\Form;

class BaseForm extends Form
{
    public function signingRules(): array
    {
        return SigningRules::rules();
    }
}
