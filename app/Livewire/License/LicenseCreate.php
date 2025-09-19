<?php

declare(strict_types=1);

namespace App\Livewire\License;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\License;
use App\Repositories\Repository;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LicenseCreate extends LicenseComponent
{
    public function mount(LegalEntity $legalEntity): void
    {
    }

    public function create(): void
    {
        if (Auth::user()?->cannot('create', License::class)) {
            Session::flash('error', 'У вас немає дозволу на створення ліцензії');

            return;
        }

        try {
            $validated = $this->form->validate();
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());

            return;
        }

        try {
            $response = EHealth::license()->create(data: removeEmptyKeys(Arr::toSnakeCase($validated)));

            try {
                Repository::license()->store($response->getData());
            } catch (Exception $exception) {
                $this->logDatabaseErrors($exception, 'Error while creating license');
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating a license');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating a license');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.license.license-create');
    }
}
