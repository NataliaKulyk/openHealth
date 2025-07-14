<?php

declare(strict_types=1);

namespace App\Livewire\License;

use Livewire\Component;
use App\Livewire\License\Forms\LicenseForm;

abstract class LicenseComponent extends Component
{
    public LicenseForm $form;

    public array $licenseTypes = [];

    public function boot(): void
    {
        $this->licenseTypes = dictionary()->getDictionary('LICENSE_TYPE');
    }
}
