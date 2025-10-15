<?php

declare(strict_types=1);

namespace App\Livewire\Division\HealthcareService;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Division\Forms\HealthcareServiceForm as Form;
use App\Models\Division;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use App\Traits\WorkTimeUtilities;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class HealthcareServiceComponent extends Component
{
    use FormTrait;
    use WorkTimeUtilities;

    public Form $form;

    public string $divisionName;

    public int $divisionId;

    public array $licenses;

    /**
     * Used to indicate is it edit page, if so update DB row instead of create new one.
     *
     * @var int|null
     */
    #[Locked]
    public ?int $healthcareServiceId = null;

    /**
     * Is in view mode.
     *
     * @var bool
     */
    public bool $isDisabled = false;

    protected array $dictionaryNames = [
        'HEALTHCARE_SERVICE_CATEGORIES',
        'SPECIALITY_TYPE',
        'PROVIDING_CONDITION',
        'HEALTHCARE_SERVICE_PHARMACY_DRUGS_TYPES'
    ];

    public function baseMount(LegalEntity $legalEntity, Division $division): void
    {
        $this->getDictionary();

        $this->dictionaries['HEALTHCARE_SERVICE_CATEGORIES'] = $this->getDictionariesFields(
            config('ehealth.healthcare_service_' . strtolower(legalEntity()->type) . '_categories', []),
            'HEALTHCARE_SERVICE_CATEGORIES'
        );
        $this->dictionaries['PROVIDING_CONDITION'] = $this->getDictionariesFields(
            config('ehealth.legal_entity_' . strtolower(legalEntity()->type) . '_providing_conditions', []),
            'PROVIDING_CONDITION'
        );

        $this->divisionName = $division->name;
        $this->form->divisionId = $division->uuid;
        $this->divisionId = $division->id;

        $this->licenses = $legalEntity->licenses()->get(['id', 'uuid', 'type'])->toArray();
    }

    public function create(): void
    {
        // Check permission for edit or create
        if (isset($this->healthcareServiceId)) {
            $healthcareService = HealthcareService::find($this->healthcareServiceId);
            if (Auth::user()?->cannot('update', $healthcareService)) {
                Session::flash('error', 'У вас немає дозволу на редагування цієї послуги');

                return;
            }
        } elseif (Auth::user()?->cannot('create', HealthcareService::class)) {
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
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ.");

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

            // Update if data from edit page(draft) or create.
            if (isset($this->healthcareServiceId)) {
                $validated['id'] = $this->healthcareServiceId;
                Repository::healthcareService()->update($response->map($validated));
            } else {
                Repository::healthcareService()->store($response->map($validated));
            }

            Session::flash('success', 'Послугу успішно створено.');
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
}
