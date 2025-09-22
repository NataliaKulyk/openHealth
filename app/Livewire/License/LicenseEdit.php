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

/**
 * Class for updating an additional license. Primary license can't be updated, see: https://e-health-ua.atlassian.net/wiki/spaces/ESOZ/pages/17533829974/BP-ESOZ-003-0003+MIS
 */
class LicenseEdit extends LicenseComponent
{
    public function mount(LegalEntity $legalEntity, License $license): void
    {
        $this->uuid = $license->uuid;
        $this->form->fill($license);
    }

    public function update(): void
    {
        if (Auth::user()?->cannot('update', License::whereUuid($this->uuid)->first())) {
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
            $response = EHealth::license()->update($this->uuid, Arr::toSnakeCase($validated));

            try {
                Repository::license()->update($response->getData());
            } catch (Exception $exception) {
                $this->logDatabaseErrors($exception, 'Error while updating license');
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when updating a license');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when updating a license');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.license.license-edit');
    }
}
