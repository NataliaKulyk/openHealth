<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
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
        $this->isIncapacitated = Person::whereId($this->personId)->whereHas('confidantPerson')->exists();
        $this->baseMount();
        $this->getPatient();
    }

    public function update(): void
    {
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', 'У вас немає дозволу на оновлення пацієнта.');

            return;
        }

        $this->form->addresses = $this->address;
        $this->form->confidantPerson = $this->confidantPerson;

        try {
            $validated = $this->form->rulesForModelValidate(['person', 'documents', 'documentsRelationship']);
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }
        unset($validated['person']['authenticationMethods'], $validated['person']['confidantPerson']);

        $formatted = $this->form->formatForApi(array_merge($validated, ['addresses' => $this->form->addresses]));
        $formatted['person']['id'] = $this->uuid;

        try {
            // update
            $response = EHealth::personRequest()->create($formatted);

            $responseData = $response->getData();
            $responseStatusCode = $response->getStatusCode();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when creating person request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating a person request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        if ($responseStatusCode === 201) {
            if (isset($this->personId)) {
                $responseData['dbId'] = $this->personId;
            }

            if (isset($responseData['person']['confidant_person'])) {
                $responseData['person']['confidant_person']['confidantPersonInfo'] = Arr::toSnakeCase(
                    $this->confidantPerson[0]
                );
            }

            // save in DB
            try {
                Repository::person()->savePersonResponseData($responseData, PersonRequest::class);
            } catch (Throwable $exception) {
                $this->logDatabaseErrors($exception, 'Failed to store person request');
                Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }

            $this->form->person['id'] = $responseData['id'];
            $this->uploadedDocuments = $response->getUrgent()['documents'];
            $this->viewState = 'new';
        }
    }

    /**
     * Get all data about the patient from the DB.
     *
     * @return void
     */
    protected function getPatient(): void
    {
        $patientData = Person::whereId($this->personId)->firstOrFail();

        // Format data
        $result = [
            'person' => array_merge($patientData->toArray(), [
                'phones' => count($patientData->phones) === 0
                    ? [['type' => null, 'number' => null]]
                    : $patientData->phones->toArray(),
                'authentication_methods' => $patientData->authenticationMethods->toArray()
            ]),
            'documents' => $patientData->documents->toArray(),
            'address' => $patientData->addresses->toArray(),
            'confidantPerson' => $patientData->confidantPerson?->toArray() ?? []
        ];

        $result = Arr::toCamelCase($result);
        $this->form->fill($result);
        $this->address = $result['address'][0];
        $this->confidantPerson = !empty($result['confidantPerson'])
            ? [$result['confidantPerson']]
            : [];
        $this->selectedConfidantPersonId = $result['confidantPerson']['personUuid'] ?? null;
        $this->form->documentsRelationship = $result['confidantPerson']['documentsRelationship'] ?? [];
    }

    public function render(): View
    {
        return view('livewire.person.person-edit');
    }
}
