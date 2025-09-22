<?php

declare(strict_types=1);

namespace App\Livewire\Patient\Records;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Patient\Records\Forms\PatientForm as Form;
use App\Models\Person\Person;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Validation\ValidationException;

class PatientData extends BasePatientComponent
{
    use FormTrait;

    public Form $form;

    public string $firstName;

    public string $lastName;

    public array $phones = [];

    public array $confidantPersonRelationships;

    /**
     * List of patient authentication methods.
     * @var array
     */
    public array $authenticationMethods;

    /**
     * ID that returns after createAuthMethod request, need for resendSMS request.
     * @var string
     */
    protected string $authMethodId;

    /**
     * ID that returns after createAuthMethod request, need for resendSMS request.
     * @var string
     */
    protected string $authMethodRequestId;

    protected function initializeComponent(): void
    {
        $patient = Person::with('phones')
            ->where('id', $this->patientId)
            ->first()
            ?->toArray();

        $this->firstName = $patient['first_name'];
        $this->lastName = $patient['last_name'];
        $this->phones = $patient['phones'] ?? [];
    }

    /**
     * Get patient verification status.
     *
     * @return void
     */
    public function getVerificationStatus(): void
    {
        try {
            $response = EHealth::person()->getPersonVerificationDetails($this->uuid);

            try {
                Repository::person()->updateVerificationStatusById(
                    $this->uuid,
                    $response->getData()['verification_status']
                );

                $this->verificationStatus = $response->getData()['verification_status'];
            } catch (Exception $exception) {
                $this->logDatabaseErrors($exception, 'Error when updating person verification status');
                session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when getting person verification details');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting person verification details');
            session()?->flash('error', 'Не вдалося отримати верифікаційний статус. Спробуйте пізніше.');
        }
    }

    /**
     * Get patient confidant persons.
     *
     * @return void
     */
    public function getConfidantPersons(): void
    {
        try {
            $response = EHealth::person()->getConfidantPersonRelationships($this->uuid, ['isExpired' => false]);

            $this->confidantPersonRelationships = $response->getData();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when getting confidant person relationships');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting confidant person relationships');
            session()?->flash('error', 'Не вдалося отримати законного представника. Спробуйте пізніше.');
        }
    }

    /**
     * Get patient authentication methods.
     *
     * @return void
     */
    public function getAuthenticationMethods(): void
    {
        try {
            $response = EHealth::person()->getAuthMethods($this->uuid);

            $this->authenticationMethods = $response->getData();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when getting auth methods');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when getting auth methods');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');
        }
    }

    /**
     * Deactivate authentication method.
     *
     * @param  array  $data
     * @return void
     */
    public function deactivateAuthMethod(array $data): void
    {
        $this->form->action = 'DEACTIVATE';
        $this->form->authenticationMethod = $data;

        try {
            $validated = $this->form->validate($this->form->rulesForDeactivate());
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        try {
            EHealth::person()->createAuthMethod($this->uuid, Arr::toSnakeCase($validated));
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when deactivating auth method request');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when deactivating auth method request');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    /**
     * Create an authentication method request.
     *
     * @param  array  $data
     * @return void
     */
    public function createAuthMethod(array $data): void
    {
        $this->form->action = 'INSERT';
        $this->form->authenticationMethod = $data;

        try {
            $validated = $this->form->validate($this->form->rules());
        } catch (ValidationException $exception) {
            session()?->flash('error', $exception->validator->errors()->first());

            return;
        }

        try {
            $response = EHealth::person()->createAuthMethod($this->uuid, Arr::toSnakeCase(removeEmptyKeys($validated)));

            if ($response->getStatusCode() === 200) {
                $this->authMethodId = $response->getData()['id'];
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when creating auth method request');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when creating auth method request');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    /**
     * Re-send SMS.
     *
     * @return void
     */
    public function resendSms(): void
    {
        try {
            $response = EHealth::person()->resendAuthOtp($this->uuid, $this->authMethodId);

            if ($response->getData()['status'] === 'new') {
                session()?->flash('success', 'SMS успішно надіслано!');
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when resending sms to person');
            session()?->flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error when resending sms to person');
            session()?->flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.patient.records.patient-data');
    }
}
