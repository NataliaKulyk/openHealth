<?php

namespace App\Livewire\License;

use App\Models\LegalEntity;
use Livewire\Component;
use App\Models\License;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class LicenseShow extends Component
{
    public $license;
    public array $licenseTypes = [];
    public string $license_type = '';
    public string $licenseTypeDescription = '';

    public function mount(LegalEntity $legalEntity, $id)
    {
        $cacheKey = "license_{$id}";
        $legal_entity_id = legalEntity()->id;

        // Check if the license is in the cache
        $this->license = Cache::remember($cacheKey, 60*60, function () use ($id, $legal_entity_id) {
            return License::where('id', $id)
                            ->where('legal_entity_id', $legal_entity_id)
                            ->firstOrFail();
        });

        $licenseTypes = dictionary()->getDictionaries([]) ?? [];

        $this->license['type_value'] = $licenseTypes[$this->license['type']]
                                    ?? 'LEGAL_ENTITY_' . $this->license['type'] . '_ADDITIONAL_LICENSE_TYPE';
    }

    public function render()
    {
        return view('livewire.license.license-view');
    }

    public function back()
    {
        return redirect()->route('license.index', [legalEntity()]);
    }
}
