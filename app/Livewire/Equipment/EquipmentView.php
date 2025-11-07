<?php

declare(strict_types=1);

namespace App\Livewire\Equipment;

use App\Models\Equipment;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EquipmentView extends EquipmentComponent
{
    public function mount(LegalEntity $legalEntity, Equipment $equipment): void
    {
        $this->baseMount($legalEntity);

        $equipment->loadMissing(['names', 'recorder:id,uuid', 'division:id,uuid']);

        $formData = $equipment->toArray();

        $formData['recorder'] = $equipment->recorder()->value('uuid');
        $formData['divisionId'] = $equipment->division()->value('uuid');

        $this->form->fill($formData);
    }

    public function render(): View
    {
        return view('livewire.equipment.equipment-view');
    }
}
