<?php

declare(strict_types=1);

namespace App\Livewire\Division\HealthcareService;

use App\Core\Arr;
use App\Enums\Status;
use App\Models\Division;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class HealthcareServiceCreate extends HealthcareServiceComponent
{
    public function mount(LegalEntity $legalEntity, Division $division): void
    {
        $this->baseMount($legalEntity, $division);
    }

    public function createLocally(): void
    {
        if (Auth::user()?->cannot('create', HealthcareService::class)) {
            Session::flash('error', 'У вас немає дозволу на створення послуги');

            return;
        }

        try {
            $validated = $this->form->doValidation();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        // Store in local database
        try {
            $validated['divisionId'] = $this->divisionId;
            $validated['legalEntityId'] = legalEntity()->id;
            $validated['status'] = Status::DRAFT;

            Repository::healthcareService()->store(Arr::toSnakeCase($validated));

            Session::flash('success', 'Чернетку послуги успішно створено.');
            $this->redirectRoute(
                'division.healthcare-service.index',
                [legalEntity(), $this->divisionId],
                navigate: true
            );
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, 'Failed to store healthcare service');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.division.healthcare-service.healthcare-service-create');
    }
}
