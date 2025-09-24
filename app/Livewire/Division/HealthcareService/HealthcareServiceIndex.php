<?php

declare(strict_types=1);

namespace App\Livewire\Division\HealthcareService;

use App\Classes\eHealth\EHealth;
use App\Enums\Status;
use App\Livewire\Division\Forms\HealthcareServiceForm as HealthCareFormRequest;
use App\Models\Division;
use App\Models\HealthcareService as HealthcareServiceModel;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Exception;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;
use Livewire\WithPagination;
use Log;
use Symfony\Component\HttpFoundation\RedirectResponse;

class HealthcareServiceIndex extends Component
{
    use WithPagination;
    use FormTrait;

    public HealthCareFormRequest $formService;

    public Division $division;

    public string $mode = 'create';

    public ?array $tableHeaders = [];

    /**
     * Values of possible allowed categories
     *
     * @var array $healthcareCategoriesKeys
     */
    protected array $healthcareCategoriesKeys = ['MSP'];

    /**
     * Current selected category
     *
     * @var string|null $category
     */
    public ?string $category = '';

    public ?array $speciality_type_msp_keys = [
        'PHARMACIST', '"PHARMACEUTICS_ORGANIZATION', 'CLINICAL_PROVISOR',
        'ANALYTICAL_AND_CONTROL_PHARMACY', 'PHARMACEUTICS_ORGANIZATION'
    ];

    public ?array $speciality_type_inpatient_keys = [
        'GENERAL_SURGERY', 'ANAESTHETICS', 'NARCOLOGY', 'THORACIC_SURGERY', 'VASCULAR_SURGERY',
        'NEUROSURGERY', 'SURGICAL_ONCOLOGY', 'RADIATION_THERAPY', 'COMBUSTIOLOGY', 'INTENSIVE_THERAPY',
        'PEDIATRIC_SURGERY', 'TRANSPLANTOLOGY', 'ORAL_AND_MAXILLOFACIAL_SURGERY', 'PLASTIC_SURGERY',
        'SURGICAL_OPHTHALMOLOGY', 'GYNECOLOGIC_ONCOLOGY', 'CARDIOVASCULAR_SURGERY', 'PATHOLOGIC_ANATOMY'
    ];

    public ?array $speciality_type;

    /**
     * @var true
     */
    public bool $license_show;

    public bool $divisionStatus = false;

    public function mount(LegalEntity $legalEntity, Division $division)
    {
        $this->dictionaries = [
            'show' => [],
            'modal' => []
        ];

        $this->division = $division;

        $this->divisionStatus = $this->division->status === Status::ACTIVE;

        $this->category = $this->healthcareCategoriesKeys[0];

        $this->prepareDictionaries();

        $this->initHealthcareService();

        $this->tableHeadersHealthcare();
    }

    public function initHealthcareService()
    {
        // HEALTHCARE_SERVICE_CATEGORY firmly pinned up to the 'MSP' for now
        $this->formService->setHealthcareServiceParam('category', $this->category);

        if ($this->category === 'MSP') {
            $this->formService->setHealthcareServiceParam('providing_condition', 'OUTPATIENT');
        }

        $this->changeCategory($this->category);
    }

    protected function prepareDictionaries(): void
    {
        $healthcareServiceCategories = dictionary()->getDictionary('HEALTHCARE_SERVICE_CATEGORIES', false)
            ->allowedKeys($this->healthcareCategoriesKeys)
            ->toArrayRecursive();
        $specialityType = dictionary()->getDictionary('SPECIALITY_TYPE', false)
            ->allowedKeys($this->speciality_type_msp_keys)
            ->toArrayRecursive();
        $providingCondition = dictionary()->getDictionary('PROVIDING_CONDITION');

        // Using for HealthcareServices main page (in table)
        $this->dictionaries['show'] = [
            'HEALTHCARE_SERVICE_CATEGORIES' => $healthcareServiceCategories,
            'SPECIALITY_TYPE' => $specialityType,
            'PROVIDING_CONDITION' => $providingCondition
        ];

        // Use within modal dialog window
        $this->dictionaries['modal'] = $this->dictionaries['show'];
    }

    #[On('refreshPage')]
    public function refreshPage()
    {
        $this->dispatch('$refresh');
    }

    public function closeModal(): void
    {
        $this->showModal = false;

        $this->formService->healthcareServiceClean($this->category);

        $this->dispatch('refreshPage');
    }

    public function create(): void
    {
        $this->formService->healthcareServiceClean();

        $this->mode = 'create';

        $this->initHealthcareService();

        $this->resetErrorBag();

        $this->openModal();
    }

    public function store(): void
    {
        $this->resetErrorBag();

        $error = $this->formService->doValidation($this->mode);

        if ($error) {
            $this->dispatch('flashMessage', ['message' => $error, 'type' => 'error']);
        } else {
            if (! $this->updateOrCreate()) {
                return;
            }
        }

        $this->closeModal();
    }

    public function update(): void
    {
        $error = $this->formService->doValidation($this->mode);

        if ($error) {
            $this->dispatch('flashMessage', ['message' => $error, 'type' => 'error']);
        } else {
            if (! $this->updateOrCreate()) {
                return;
            }
        }

        $this->closeModal();
    }

    /**
     * Combined method used both creation and modification Division's data
     *
     * @return Redirector|RedirectResponse|null
     */
    public function updateOrCreate(): Redirector|RedirectResponse|null
    {
        $response = $this->mode === 'edit'
            ? $this->updateHealthcareService()
            : $this->createHealthcareService();

        if ($response) {
            Repository::healthcareService()
                ->setDivision($this->division)
                ->saveHealthcareServiceResponseData($response);

            return redirect()->route('healthcare_service.index', [legalEntity(), $this->division])->with('success', __('forms.success_response'));
        }

        session()->flash('error', __('errors.ehealth.messages.request_error'));

        return null;
    }

    private function updateHealthcareService(): array|null
    {
        $uuid = $this->formService->getHealthcareServiceParam('uuid');

        $healthcareServiceRawData = $this->formService->getHealthcareService();

        $requestParams = Repository::healthcareService()->prepareRequestUpdateData($healthcareServiceRawData);

        try {
            return EHealth::healthcareService()->update(uuid: $uuid, data: $requestParams)->validate();
        } catch (Exception $err) {
            Log::error(self::class . ':updateHealthcareService', ['error' => $err->getMessage()]);
        }

        return null;
    }

    private function createHealthcareService(): array|null
    {
        $healthcareServiceRawData = $this->formService->getHealthcareService();

        $requestParams = Repository::healthcareService()
            ->setDivision($this->division)
            ->prepareRequestCreateData($healthcareServiceRawData);

        try {
            return EHealth::healthcareService()->create(data: $requestParams)->validate();
        } catch (Exception $err) {
            Log::error(self::class . ':createHealthcareService', ['error' => $err->getMessage()]);
        }

        return null;
    }

    public function activate(HealthcareServiceModel $healthcareService): void
    {
        try {
            $response = EHealth::healthcareService()->activate($healthcareService->uuid);

            if (! $response->successful()) {
                throw new Exception('response_error ' . $response->body());
            }

            $responseData = $response->getData();

            Repository::healthcareService()->setAction($healthcareService, $responseData['status']);
        } catch (Exception $err) {
            Log::error(self::class . ':activate:', ['message' => $err->getMessage()]);

            session()->flash('error', __('Цю послугу не вдалось активувати'));
        }
    }

    public function deactivate(HealthcareServiceModel $healthcareService): void
    {
        try {
            $response = EHealth::healthcareService()->deactivate($healthcareService->uuid);

            if (! $response->successful()) {
                throw new Exception('response_error ' . $response->body());
            }

            $responseData = $response->getData();

            Repository::healthcareService()->setAction($healthcareService, $responseData['status']);
        } catch (Exception $err) {
            Log::error(self::class . ':deactivate:', ['message' => $err->getMessage()]);

            session()->flash('error', __('Цю послугу не вдалось деактивувати'));
        }
    }

    public function sync(): void
    {
        $response = null;

        try {
            $response = EHealth::healthcareService()->getMany(divisionUuid: $this->division->uuid);

            $healthcareServices = $response->validate();

            Repository::healthcareService()
                ->setDivision($this->division)
                ->saveHealthcareServiceList($healthcareServices);
        } catch (Exception $err) {
            Log::error('HealthscareService repository [syncHealthcareServiceList]: ', ['error' => $err->getMessage()]);

            session()->flash('error', __('Помилка синхронізації. Зверніться до адміністратора.'));

            return;
        }

        if ($response?->isNotLast()) {
            // TODO run
            dd('HCS Multi-Paging detected', $response->getPaging());
            // SyncHealthsCareListJob::dispatch(legalEntity(), 2); // page starts from number 2
        }

        session()->flash('success', __('Інформацію успішно оновлено'));
    }

    public function tableHeadersHealthcare(): void
    {
        $this->tableHeaders = [
            __('ID E-health '),
            __('forms.category'),
            __('Умови надання'),
            __('Тип спеціальності'),
            __('Статус'),
            __('forms.action'),
        ];
    }

    public function changeCategory($type): void
    {
        $this->category = $type;

        $this->dictionaries['modal']['PROVIDING_CONDITION'] = $type === 'MSP'
            ? dictionary()->getDictionary('PROVIDING_CONDITION', false)
                ->allowedKeys(['OUTPATIENT'])
                ->toArrayRecursive()
            : dictionary()->getDictionary('PROVIDING_CONDITION');

        // if ($category === 'PHARMACY_DRUGS') {
        //     $this->speciality_type_msp_keys = ["PHARMACIST", "PROVISOR", "CLINICAL_PROVISOR"];
        //     $this->specialityType();
        //     $this->license_show = true;
        // }
    }

    public function specialityType(): void
    {
        $this->dictionaries['modal']['SPECIALITY_TYPE'] = array_intersect_key(
            $this->speciality_type,
            array_flip($this->speciality_type_msp_keys)
        );
    }

    public function changeProvidingCondition($type): void
    {
        $currentProvidingCondition = $this->formService->getHealthcareServiceParam('providing_condition') ?? '';

        if ($currentProvidingCondition === 'INPATIENT') {
            $this->dictionaries['modal']['SPECIALITY_TYPE'] = dictionary()->getDictionary('SPECIALITY_TYPE', false)
                ->allowedKeys($this->speciality_type_inpatient_keys)
                ->toArrayRecursive();
        } else {
            $this->dictionaries['modal']['SPECIALITY_TYPE'] = dictionary()->getDictionary('SPECIALITY_TYPE', false)
                ->allowedKeys($this->speciality_type_msp_keys)
                ->toArrayRecursive();
        }
    }

    #[Computed]
    public function availableTime(): array
    {
        return empty($this->formService->getHealthcareServiceParam('available_time'))
            ? []
            : $this->formService->getHealthcareServiceParam('available_time');
    }

    public function addAvailableTime($k = 0): void
    {
        $this->formService->addAvailableTime($k);
    }

    public function removeAvailableTime($k): void
    {
        $this->formService->removeAvailableTime($k);
    }

    #[Computed]
    public function notAvailable(): array
    {
        return empty($this->formService->getHealthcareServiceParam('not_available'))
            ? []
            : $this->formService->getHealthcareServiceParam('not_available');
    }

    public function addNotAvailableTime(): void
    {
        $this->formService->addNotAvailableTime();
    }

    public function removeNotAvailable($k): void
    {
        $this->formService->removeNotAvailable($k);
    }

    public function render(): View
    {
        $perPage = config('pagination.per_page');
        $healthcareServices = $this->division->healthcareService()->orderBy('uuid')->paginate($perPage);
        $currentDivision['name'] = $this->division->name;
        $currentDivision['type'] = dictionary()->getDictionary('DIVISION_TYPE', false)->getValue($this->division->type);

        return view('livewire.division.healthcare-service.healthcare-service-index', compact(['healthcareServices', 'currentDivision']));
    }
}
