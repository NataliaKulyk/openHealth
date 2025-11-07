<?php

declare(strict_types=1);

namespace App\Livewire\Equipment;

use App\Enums\Equipment\Status;
use App\Models\Equipment;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Throwable;

class EquipmentEdit extends EquipmentComponent
{
    public function mount(LegalEntity $legalEntity, Equipment $equipment): void
    {
        $this->baseMount($legalEntity);
        $this->equipmentId = $equipment->id;

        $equipment->loadMissing(['names', 'recorder:id,uuid', 'division:id,uuid']);

        $formData = $equipment->toArray();

        $formData['recorder'] = $equipment->recorder()->value('uuid');
        $formData['divisionId'] = $equipment->division()->value('uuid');

        $this->form->fill($formData);
    }

    public function create(): void
    {
        $equipment = Equipment::find($this->equipmentId);
        $this->form->status = Status::ACTIVE->value;

        if (Auth::user()?->cannot('edit', $equipment)) {
            Session::flash('error', 'У вас немає дозволу на редагування обладнання');

            return;
        }

        $validated = $this->validateForm();
        if (!$validated) {
            return;
        }

        $response = $this->createInEHealth($validated);
        if (!$response) {
            return;
        }

        try {
            $validated = $response->validate();
            $validated['id'] = $this->equipmentId;
            Repository::equipment()->update($response->map($validated));

            Session::flash('success', 'Обладнання успішно створено.');
            $this->redirectRoute('equipment.index', [legalEntity()], navigate: true);
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store equipment');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.equipment.equipment-edit');
    }
}
