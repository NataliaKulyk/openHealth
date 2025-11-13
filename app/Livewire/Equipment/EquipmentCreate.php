<?php

declare(strict_types=1);

namespace App\Livewire\Equipment;

use App\Core\Arr;
use App\Enums\Equipment\AvailabilityStatus;
use App\Enums\Equipment\Status;
use App\Models\Division;
use App\Models\Employee\Employee;
use App\Models\Equipment;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Throwable;

class EquipmentCreate extends EquipmentComponent
{
    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount($legalEntity);

        $this->form->status = Status::ACTIVE->value;
        $this->form->availabilityStatus = AvailabilityStatus::AVAILABLE->value;
    }

    public function create(): void
    {
        if (Auth::user()?->cannot('create', Equipment::class)) {
            Session::flash('error', 'У вас немає дозволу на створення обладнання');

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
            Repository::equipment()->store($response->map($validated));

            Session::flash('success', 'Обладнання успішно створено.');
            $this->redirectRoute('equipment.index', [legalEntity()], navigate: true);
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store equipment');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function createLocally(): void
    {
        if (Auth::user()?->cannot('create', Equipment::class)) {
            Session::flash('error', 'У вас немає дозволу на створення обладнання');

            return;
        }

        $validated = $this->validateForm();
        if (!$validated) {
            return;
        }

        $validated['legal_entity_id'] = legalEntity()->id;
        $validated['status'] = Status::DRAFT;
        $validated['recorder'] = Employee::whereUuid($validated['recorder'])->value('id');

        if (!empty($validated['divisionId'])) {
            $validated['divisionId'] = Division::whereUuid($validated['divisionId'])->value('id');
        }

        if (!empty($validated['parentId'])) {
            $validated['parentId'] = Equipment::whereUuid($validated['parentId'])->value('id');
        }

        try {
            Repository::equipment()->store(Arr::toSnakeCase($validated));

            Session::flash('success', 'Чернетку обладнання успішно створено.');
            $this->redirectRoute('equipment.index', [legalEntity()], navigate: true);
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store equipment');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.equipment.equipment-create');
    }
}
