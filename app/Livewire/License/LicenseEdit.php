<?php

declare(strict_types=1);

namespace App\Livewire\License;

use App\Models\LegalEntity;
use App\Models\License;

/**
 * Class for updating an additional license. Primary license can't be updated, see: https://e-health-ua.atlassian.net/wiki/spaces/ESOZ/pages/17533829974/BP-ESOZ-003-0003+MIS
 */
class LicenseEdit extends LicenseComponent
{
    public function mount(LegalEntity $legalEntity, License $license): void
    {
        $this->form->setLicense($license);
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

    public function render()
    {
        return view('livewire.license.license-edit');
    }
}
