<?php

declare(strict_types=1);

namespace App\Livewire\Division\HealthcareService;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Division\Forms\HealthcareServiceForm as HealthCareFormRequest;
use App\Models\Division;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Log;
use Throwable;

class HealthcareServiceIndex extends Component
{
    use WithPagination;
    use FormTrait;

    public HealthCareFormRequest $formService;

    public ?int $divisionId = null;
    public ?string $divisionUuid = null;

    public bool $divisionStatus = true;

    public array $dictionaryNames = ['DIVISION_TYPE', 'SPECIALITY_TYPE', 'PROVIDING_CONDITION'];

    public function mount(LegalEntity $legalEntity, Division $division): void
    {
        $this->divisionId = $division->id;
        $this->divisionUuid = $division->uuid;

        $this->getDictionary();
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

    public function activate(string $uuid): void
    {
        try {
            $response = EHealth::healthcareService()->activate($uuid);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, "Error connecting when activate $uuid a healthcare service");
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, "Error when activate $uuid a healthcare service");

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            Repository::healthcareService()->updateStatus($uuid, $response->validate()['status']);

            Session::flash('success', 'Послугу успішно активовано');
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, "Failed to activate $uuid healthcare service");
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function deactivate(string $uuid): void
    {
        try {
            $response = EHealth::healthcareService()->deactivate($uuid);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, "Error connecting when deactivating $uuid a healthcare service");
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, "Error when deactivating $uuid a healthcare service");

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            Repository::healthcareService()->updateStatus($uuid, $response->validate()['status']);

            Session::flash('success', 'Послугу успішно деактивовано');
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, "Failed to deactivate $uuid healthcare service");
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function sync(): void
    {
        try {
            $response = EHealth::healthcareService()->getSeveral(query: ['division_id' => $this->divisionUuid]);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when getting a healthcare service list');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error connecting when getting a healthcare service list');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        try {
            $validated = $response->validate();
            Repository::healthcareService()->sync($response->map($validated));
        } catch (Throwable $exception) {
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');
            $this->logDatabaseErrors($exception, 'Error while synchronizing healthcare services with eHealth: ');

            return;
        }

        if ($response->isNotLast()) {
            // TODO run
        }

        Session::flash('success', __('Послуги успішно синхронізовані'));
    }

    #[Computed]
    public function healthcareServices(): LengthAwarePaginator
    {
        $query = HealthcareService::with('division:id,name')
            ->select(['uuid', 'division_id', 'speciality_type', 'providing_condition', 'created_at', 'status'])
            ->where('legal_entity_id', legalEntity()->id);

        // If divisionId is set, filter by division
        if ($this->divisionId) {
            $query->where('division_id', $this->divisionId);
        }

        $allItems = $query->get();

        // Pagination
        $perPage = config('pagination.per_page');
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $allItems->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentItems,
            $allItems->count(),
            $perPage,
            $currentPage,
            ['path' => request()->url()]
        );
    }

    public function render(): View
    {
        return view('livewire.division.healthcare-service.healthcare-service-index', [
            'healthcareServices' => $this->healthcareServices
        ]);
    }
}
