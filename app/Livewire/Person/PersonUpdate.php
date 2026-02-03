<?php

declare(strict_types=1);

namespace App\Livewire\Person;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Person\AuthStep;
use App\Models\Relations\AuthenticationMethod as AuthenticationMethodModel;
use App\Enums\Person\AuthenticationMethod;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use App\Repositories\Repository;
use App\Rules\PhoneNumber;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
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

    /**
     * List of available auth methods.
     *
     * @var array
     */
    public array $authenticationMethods;

    public bool $showAuthMethodModal = false;

    public AuthStep $authStep = AuthStep::INITIAL;

    /**
     * Current phone number.
     *
     * @var string|null
     */
    public ?string $phoneNumber = null;

    /**
     * Confirmation code that need for 'Complete OTP Verification' endpoint
     *
     * @var int
     */
    public int $code;

    /**
     * Phone number that person will be used instead of old one.
     *
     * @var string
     */
    public string $newPhoneNumber;

    /**
     * Code for approving phone number.
     *
     * @var int
     */
    public int $verificationCode;

    /**
     * ID that needed for approving auth method.
     *
     * @var string
     */
    #[Locked]
    public string $requestId;

    /**
     * UUID of auth method with which we interact.
     *
     * @var string
     */
    public string $selectedAuthMethodUuid;

    /**
     * Selected auth method type.
     *
     * @var string
     */
    public string $selectedAuthMethodType;

    /**
     * Alias name.
     *
     * @var string
     */
    public string $alias;

    public function mount(LegalEntity $legalEntity, Person $person): void
    {
        $this->personId = $person->id;
        $this->uuid = $person->uuid;
        $this->baseMount();

        $this->form->person = Arr::toCamelCase(
            $person->load([
                'addresses',
                'documents',
                'phones',
                'authenticationMethods',
                'confidantPerson.person:id,uuid,gender,last_name,first_name,second_name,tax_id,unzr',
                'confidantPerson.documentsRelationship'
            ])->toArray()
        );

        $this->address = Arr::get($this->form->person, 'addresses.0', []);

        if (empty($this->form->person['phones'])) {
            $this->form->person['phones'] = [['type' => null, 'number' => null]];
        }

        if (empty($this->form->person['emergencyContact'])) {
            $this->form->person['emergencyContact']['phones'] = [['type' => null, 'number' => null]];
        }

        $authenticationMethods = $person->authenticationMethods->toArray();

        if ($person->confidantPerson) {
            $this->selectedConfidantPersonId = $person->confidantPerson->person->uuid;
            $confidantPersonData = $person->confidantPerson->person;

            // Change id to uuid value
            $confidantPerson = $confidantPersonData->toArray();
            $confidantPerson['id'] = $confidantPerson['uuid'];
            unset($confidantPerson['uuid']);

            $this->confidantPerson = [$confidantPerson];

            $modifiedMethods = collect($authenticationMethods)->map(
                function (array $method) use ($confidantPersonData) {
                    if ($method['type'] === AuthenticationMethod::THIRD_PERSON->value) {
                        $method['confidantPerson'] = [
                            'name' => $confidantPersonData->fullName,
                            'taxId' => $confidantPersonData->taxId,
                            'unzr' => $confidantPersonData->unzr,
                            'documentsPerson' => $confidantPersonData->documents->toArray()
                        ];
                    }

                    return $method;
                }
            );

            $this->authenticationMethods = $modifiedMethods->toArray();
        } else {
            $this->authenticationMethods = $authenticationMethods;
            $this->phoneNumber = collect($authenticationMethods)
                ->where('type', AuthenticationMethod::OTP->value)
                ->pluck('phoneNumber')
                ->first();
        }
    }

    /**
     * Show modal for choosing authorize with param.
     *
     * @return void
     */
    public function openAuthMethodModal(): void
    {
        $this->showAuthMethodModal = true;
        $this->authStep = AuthStep::INITIAL;

        try {
            $response = EHealth::person()->getAuthMethods($this->uuid);
            $newAuthMethods = collect($response->validate())
                ->map(fn (array $item) => Arr::except($item, 'confidant_person'));
            $person = Person::whereUuid($this->uuid)->firstOrFail();
            $incomingTypes = collect($newAuthMethods)->pluck('type')->filter()->values();

            // Delete unrelated
            $person->authenticationMethods()
                ->whereNotIn('type', $incomingTypes)
                ->delete();

            // Update or create actual by type
            foreach ($newAuthMethods as $method) {
                $person->authenticationMethods()->updateOrCreate(
                    ['type' => $method['type']],
                    $method
                );
            }

            $this->authenticationMethods = Arr::toCamelCase($response->map($response->validate()));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when getting auth methods');

            return;
        }
    }

    /**
     * Set auth data for future interaction.
     *
     * @param  string  $uuid
     * @param  string  $type
     * @param  AuthStep  $step
     * @return void
     */
    public function selectAuthMethod(string $uuid, string $type, AuthStep $step): void
    {
        $this->selectedAuthMethodUuid = $uuid;
        $this->selectedAuthMethodType = $type;
        $this->authStep = $step;
    }

    /**
     * Update data for created person.
     *
     * @return void
     */
    public function update(): void
    {
        if (Auth::user()->cannot('create', PersonRequest::class)) {
            Session::flash('error', __('patients.policy.update'));

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
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when updating a person request');

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

    public function createOtpAuthMethod(): void
    {
        $this->changePhoneNumber($this->newPhoneNumber);
    }

    public function createOfflineAuthMethod(): void
    {
        try {
            $response = EHealth::person()->insertAuthMethod($this->uuid, AuthenticationMethod::OFFLINE);

            $this->requestId = $response->validate()['id'];
            $this->uploadedDocuments = $response->validate()['documents'];
            $this->authStep = AuthStep::CHANGE_FROM_OFFLINE;
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when creating auth method request');

            return;
        }
    }

    public function approveCreatingOffline(): void
    {
        try {
            $this->uploadDocuments();
            $response = EHealth::person()->approveAuthMethod($this->uuid, $this->requestId);

            // Update uuid and type with approved
            Person::whereUuid($this->uuid)->firstOrFail()
                ->authenticationMethods()
                ->create($response->validate());

            $this->showAuthMethodModal = false;
            Session::flash('success', __('Метод автентифікації через документи успішно додано'));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when approving offline auth method');

            return;
        }
    }

    /**
     * Verify is current phone number belongs to person.
     *
     * @return void
     */
    public function verifyOwnership(): void
    {
        try {
            $validated = $this->validate(['form.phoneNumber' => ['required', new PhoneNumber()]]);
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            $response = EHealth::verification()->findByPhoneNumber($validated['form']['phoneNumber']);

            // If phone number is found, it means that phone number is verified, so we move to step with changing number
            if ($response->validate()['phone_number'] === $validated['form']['phoneNumber']) {
                $this->changePhoneNumber($response->validate()['phone_number']);

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error when finding for OTP verification');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            // If you get error then it means that number is no verify, then initialize phone verification
            if ($exception->getCode() === 404) {
                try {
                    EHealth::verification()->initialize(Arr::toSnakeCase($validated));
                    $this->authStep = AuthStep::VERIFY_PHONE;
                } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
                    $this->handleEHealthExceptions($exception, 'Error when initialize OTP verification request');

                    return;
                }
            }
        }
    }

    /**
     * Complete OTP verification.
     *
     * @return void
     */
    public function completeVerifyingOwnership(): void
    {
        try {
            $validated = $this->validate(['code' => ['required', 'integer']]);
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

        try {
            EHealth::verification()->complete($this->form->phoneNumber, $validated);
            $this->authStep = AuthStep::COMPLETE_VERIFICATION;
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when complete OTP verification request');

            return;
        }
    }

    /**
     * Update phone number with verified new number.
     *
     * @return void
     */
    public function updatePhoneNumber(): void
    {
        $this->changePhoneNumber($this->newPhoneNumber);
    }

    /**
     * Approve phone number with verification code.
     *
     * @return void
     */
    public function approveUpdatingPhoneNumber(): void
    {
        $validated = $this->validate(['verificationCode' => ['required', 'digits:4']]);

        try {
            $response = EHealth::person()->approveAuthMethod(
                $this->uuid,
                $this->requestId,
                Arr::toSnakeCase($validated)
            );

            // Update uuid with approved
            Person::whereUuid($this->uuid)->firstOrFail()
                ->authenticationMethods()
                ->whereType(AuthenticationMethod::OTP)
                ->update(['uuid' => $response->validate()['id']]);

            $this->showAuthMethodModal = false;
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when approving changing auth phone number');

            return;
        }
    }

    /**
     * Approve changing auth method type from OFFLINE to OTP.
     *
     * @return void
     */
    public function approveChangingType(): void
    {
        try {
            $this->uploadDocuments();
            $response = EHealth::person()->approveAuthMethod($this->uuid, $this->requestId);

            // Update uuid and type with approved
            Person::whereUuid($this->uuid)->firstOrFail()
                ->authenticationMethods()
                ->whereType(AuthenticationMethod::OFFLINE)
                ->update(['uuid' => $response->validate()['id'], 'type' => AuthenticationMethod::OTP]);

            $this->showAuthMethodModal = false;
            Session::flash('success', __('Метод автентифікації успішно змінений із документів на СМС'));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when approving auth method (from OFFLINE to OTP)');

            return;
        }
    }

    /**
     * Update alias name in auth method
     *
     * @return void
     */
    public function updateAliasName(): void
    {
        $validated = $this->validate(['alias' => ['required', 'string', 'max:255']]);

        try {
            $response = EHealth::person()->updateAuthMethod(
                $this->uuid,
                $this->selectedAuthMethodUuid,
                $validated['alias']
            );
            $this->requestId = $response->validate()['id'];

            if ($this->selectedAuthMethodType === AuthenticationMethod::OFFLINE->value) {
                $this->uploadedDocuments = $response->validate()['documents'];
            }
            $this->authStep = AuthStep::UPDATE_ALIAS;
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when updating alias auth method');

            return;
        }
    }

    /**
     * Update alias of auth method.
     *
     * @return void
     */
    public function approveUpdatingAlias(): void
    {
        try {
            if ($this->selectedAuthMethodType === AuthenticationMethod::OFFLINE->value) {
                $this->uploadDocuments();
                EHealth::person()->approveAuthMethod($this->uuid, $this->requestId);
            } else {
                $validated = $this->validate(['verificationCode' => ['required', 'digits:4']]);
                EHealth::person()->approveAuthMethod($this->uuid, $this->requestId, Arr::toSnakeCase($validated));
            }

            // Update alias value
            AuthenticationMethodModel::whereUuid($this->selectedAuthMethodUuid)->update(['alias' => $this->alias]);

            $this->showAuthMethodModal = false;
            Session::flash('success', __('Назва методу автентифікації успішно змінена.'));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when approving auth method request');

            return;
        }
    }

    /**
     * Resend code to phone number.
     *
     * @return void
     */
    public function resendCode(): void
    {
        try {
            EHealth::person()->resendAuthOtp($this->uuid, $this->requestId);
            Session::flash('success', __('Код був повторно надісланий на телефон'));
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when resending SMS');

            return;
        }
    }

    /**
     * Change phone number with new one.
     *
     * @param  string  $phoneNumber
     * @return void
     */
    protected function changePhoneNumber(string $phoneNumber): void
    {
        $validated = Validator::make(
            ['newPhoneNumber' => $phoneNumber],
            ['newPhoneNumber' => 'required', new PhoneNumber()]
        )->validate();

        try {
            $response = EHealth::person()->insertAuthMethod(
                $this->uuid,
                AuthenticationMethod::OTP,
                $validated['newPhoneNumber']
            );
            $this->requestId = $response->validate()['id'];
            $this->uploadedDocuments = $response->validate()['documents'];

            // If the change type from OTP to Offline, then show the step, request to change the phone number
            if ($this->selectedAuthMethodType === AuthenticationMethod::OFFLINE->value) {
                $this->authStep = AuthStep::CHANGE_FROM_OFFLINE;
            } else {
                $this->authStep = AuthStep::CHANGE_PHONE;
            }
        } catch (ConnectionException|EHealthValidationException|EHealthResponseException $exception) {
            $this->handleEHealthExceptions($exception, 'Error when creating auth method request');

            return;
        }
    }

    public function render(): View
    {
        return view('livewire.person.person-edit');
    }
}
