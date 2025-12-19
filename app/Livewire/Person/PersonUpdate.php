<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Person\AuthenticationMethod;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use App\Repositories\Repository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Throwable;

/**
 * Used for updating person by using person request call
 */
class PersonUpdate extends PersonComponent
{
    #[Locked]
    public string $uuid;

    public function mount(LegalEntity $legalEntity, Person $person): void
    {
        $this->personId = $person->id;
        $this->uuid = $person->uuid;
        $this->baseMount();

        $this->form->person = Arr::toCamelCase($person->load(['addresses', 'documents', 'phones'])->toArray());

        $this->address = $this->form->person['addresses'][0];

        if (empty($this->form->person['phones'])) {
            $this->form->person['phones'] = [['type' => null, 'number' => null]];
        }

        if ($person->confidantPerson) {
            $this->selectedConfidantPersonId = $person->confidantPerson->person->uuid;

            $authorizeWith = $person->authenticationMethods
                ->where('type', AuthenticationMethod::THIRD_PERSON)
                ->first()
                ?->uuid;

            // If uuid isn't found, do request and save it in DB
            if (!$authorizeWith) {
                try {
                    $response = EHealth::person()->getAuthMethods($this->uuid);

                    $newAuthMethods = $response->validate();

                    // Update by type
                    foreach ($newAuthMethods as $method) {
                        $person->authenticationMethods()->updateOrCreate(
                            ['type' => $method['type']],
                            $method
                        );
                    }

                    $this->form->authorizeWith = $newAuthMethods[0]['uuid'];
                } catch (ConnectionException $exception) {
                    $this->logConnectionError($exception, 'Error connecting when getting auth methods');
                    Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

                    return;
                } catch (EHealthValidationException|EHealthResponseException $exception) {
                    $this->logEHealthException($exception, 'Error when getting auth methods');

                    if ($exception instanceof EHealthValidationException) {
                        Session::flash('error', $exception->getFormattedMessage());
                    } else {
                        Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
                    }

                    return;
                }
            } else {
                $this->form->authorizeWith = $authorizeWith;
            }
        }
    }

    /**
     * Update data for created person.
     *
     * @return void
     */
    public function update(): void
    {
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на оновлення пацієнта.');

            return;
        }

        $this->form->person['addresses'] = [$this->address]; // must be multiple

        try {
            $addressErrors = $this->addressValidation();
            if (!empty($addressErrors)) {
                throw ValidationException::withMessages($addressErrors);
            }

            $validated = $this->form->validate($this->form->rulesForUpdate());
            $this->formKey++;
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());
            $this->formKey++;

            return;
        }

        $formatted = $this->form->formatForPersonCreationApi(
            array_merge($validated, ['addresses' => $this->form->addresses])
        );
        $formatted['person']['id'] = $this->uuid;

        try {
            // update
            $response = EHealth::personRequest()->create($formatted);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when updating person request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when updating a person request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        if ($response->successful()) {
            // save in DB
            try {
                Repository::personRequest()->update(removeEmptyKeys($response->map($response->validate())));
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Failed to update person request');
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }

            $this->form->person['id'] = $response->getData()['id'];
            $this->uploadedDocuments = $response->getUrgent()['documents'];
            $this->viewState = 'new';
        }
    }

    public function render(): View
    {
        return view('livewire.person.person-edit');
    }
}
