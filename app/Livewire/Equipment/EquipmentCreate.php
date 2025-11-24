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
        if (Auth::user()->cannot('create', Equipment::class)) {
            Session::flash('error', 'У вас немає дозволу на створення обладнання');

            return;
        }

        $validated = $this->validateForm();
        if (!$validated) {
            return;
        }

        $apiPayload = $this->form->formatForApi($validated);

        $response = $this->createInEHealth($apiPayload);
        if (!$response) {
            return;
        }

        try {
            $validated = $response->validate();
            Repository::equipment()->store($response->map($validated));

            Session::flash('success', __('equipments.success.created'));
            $this->redirectRoute('equipment.index', [legalEntity()], navigate: true);
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store equipment');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function createLocally(): void
    {
        if (Auth::user()->cannot('create', Equipment::class)) {
            Session::flash('error', 'У вас немає дозволу на створення обладнання');

            return;
        }

        $validated = $this->validateForm();
        if (!$validated) {
            return;
        }

        $apiPayload = $this->form->formatForApi($validated);

        $apiPayload['legalEntityId'] = legalEntity()->id;
        $apiPayload['status'] = Status::DRAFT;
        $apiPayload['recorder'] = Employee::whereUuid($apiPayload['recorder'])->value('id');

        if (!empty($apiPayload['division_id'])) {
            $apiPayload['division_id'] = Division::whereUuid($apiPayload['division_id'])->value('id');
        }

        if (!empty($apiPayload['parent_id'])) {
            $apiPayload['parent_id'] = Equipment::whereUuid($apiPayload['parent_id'])->value('id');
        }

        try {
            Repository::equipment()->store(Arr::toSnakeCase($apiPayload));

            Session::flash('success', __('equipments.success.draft_created'));
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
