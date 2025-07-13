<?php

declare(strict_types=1);

namespace App\Livewire\License;

use App\Models\LegalEntity;

class LicenseCreate extends LicenseComponent
{
    public function mount(LegalEntity $legalEntity): void
    {

    }

    public function render()
    {
        return view('livewire.license.license-create');
    }
}
