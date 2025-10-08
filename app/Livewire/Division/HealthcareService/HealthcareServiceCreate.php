<?php

declare(strict_types=1);

namespace App\Livewire\Division\HealthcareService;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\HealthcareService;
use App\Repositories\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class HealthcareServiceCreate extends HealthcareServiceComponent
{
    public function create(): void
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

        // Create in eHealth
        try {
            $response = EHealth::healthcareService()->create(removeEmptyKeys(Arr::toSnakeCase($validated)));
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating a healthcare service');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating a healthcare service');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        // Store in local database
        try {
            $validated = $response->validate();
            Repository::healthcareService()->store($response->map($validated));

            Session::flash('success', 'Послугу успішно створено');
            $this->redirectRoute('healthcare-service.index', [legalEntity(), $this->divisionId], navigate: true);
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
