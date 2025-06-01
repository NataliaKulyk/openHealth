<?php

declare(strict_types=1);

namespace App\Livewire\License;

use App\Models\LegalEntity;
use App\Models\License;

class LicenseView extends LicenseEdit
{
    protected License $license;

    public function mount(LegalEntity $legalEntity, License $license): void
    {
        $this->license = $license;
    }

    public function render()
    {
        return view('livewire.license.license-view')->with([
            'license' => $this->license,
        ]);
    }
}
