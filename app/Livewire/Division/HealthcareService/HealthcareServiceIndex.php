<?php

declare(strict_types=1);

namespace App\Livewire\Division\HealthcareService;

use App\Classes\eHealth\EHealth;
use App\Enums\Status;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Jobs\HealthcareServiceSync;
use App\Models\Division;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use App\Notifications\SyncNotification;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Bus\Batch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class HealthcareServiceIndex extends Component
{
    use WithPagination;
    use FormTrait;

    public ?int $divisionId = null;

    public ?string $divisionUuid = null;

    public ?Status $divisionStatus;

    #[Url(as: 'type')]
    public ?string $typeFilter = null;

    /**
     * List of divisions in the current legal entity.
     *
     * @var array
     */
    public array $divisions;

    #[Url(as: 'division')]
    public ?int $divisionFilter = null;

    public bool $isFiltersApplied = false;

    public array $dictionaryNames = ['DIVISION_TYPE', 'SPECIALITY_TYPE', 'PROVIDING_CONDITION'];

    public function mount(LegalEntity $legalEntity, Division $division): void
    {
        if ($this->divisionFilter) {
            $this->isFiltersApplied = true;
        }

        $this->divisionUuid = $division->uuid;
        $this->divisions = $legalEntity->divisions()->select(['id', 'name', 'status'])->get()->toArray();

        $this->getDictionary();
    }

    public function search(): void
    {
        $this->resetPage();
        $this->isFiltersApplied = true;
    }

    public function resetFilters(): void
    {
        $this->divisionFilter = null;
        $this->typeFilter = null;
        $this->divisionId = null;
    }

    public function activate(HealthcareService $healthcareService): void
    {
        if (Auth::user()->cannot('activate', $healthcareService)) {
            Session::flash('error', 'У вас немає дозволу на активування послуги');

            return;
        }

        try {
            $response = EHealth::healthcareService()->activate($healthcareService->uuid);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, "Error connecting when activate $healthcareService->uuid a healthcare service");
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, "Error when activate $healthcareService->uuid a healthcare service");

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            Repository::healthcareService()->updateStatus($healthcareService->uuid, $response->validate());

            Session::flash('success', 'Послугу успішно активовано');
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, "Failed to activate $healthcareService->uuid healthcare service");
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function deactivate(HealthcareService $healthcareService): void
    {
        if (Auth::user()->cannot('deactivate', $healthcareService)) {
            Session::flash('error', 'У вас немає дозволу на деактивування послуги');

            return;
        }

        try {
            $response = EHealth::healthcareService()->deactivate($healthcareService->uuid);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, "Error connecting when deactivating $healthcareService->uuid a healthcare service");
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, "Error when deactivating $healthcareService->uuid a healthcare service");

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            Repository::healthcareService()->updateStatus($healthcareService->uuid, $response->validate());

            Session::flash('success', 'Послугу успішно деактивовано');
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, "Failed to deactivate $healthcareService->uuid healthcare service");
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function delete(HealthcareService $healthcareService): void
    {
        if (Auth::user()->cannot('delete', $healthcareService)) {
            Session::flash('error', 'У вас немає дозволу на видалення заявки на створення послуги');

            return;
        }

        try {
            HealthcareService::destroy($healthcareService->id);

            Session::flash('success', 'Чернетку послуги успішно видалено');
        } catch (Exception $exception) {
            $this->logDatabaseErrors($exception, 'Error while deleting healthcare service: ');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function sync(): void
    {
        if (Auth::user()->cannot('sync', HealthcareService::class)) {
            Session::flash('error', 'У вас немає дозволу на синхронізацію послуг');

            return;
        }

        try {
            $query = $this->divisionUuid ? ['division_id' => $this->divisionUuid] : null;

            $response = EHealth::healthcareService()->getMany(query: $query);
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
            Session::flash('error', 'Виникла помилка. Оновіть список місць надання послуг та спробуйте ще раз');
            $this->logDatabaseErrors($exception, 'Error while synchronizing healthcare services with eHealth: ');

            return;
        }

        // If there are more pages, dispatch a job to handle the rest
        if ($response->isNotLast()) {
            try {
                Auth::user()->notify(new SyncNotification('healthcare_service', 'started'));
                $this->dispatchNextSyncJobs();
                Session::flash('success', __('Синхронізацію успішно розпочато.'));
            } catch (Throwable $exception) {
                Log::error('Failed to dispatch HealthcareServiceSync batch', ['exception' => $exception]);

                Auth::user()->notify(new SyncNotification('healthcare_service', 'failed'));
            }
        } else {
            Session::flash('success', __('Інформацію успішно оновлено'));
        }
    }

    #[Computed]
    public function healthcareServices(): LengthAwarePaginator
    {
        $query = HealthcareService::filterByLegalEntity(legalEntity()->id);

        // Filters
        if ($this->isFiltersApplied) {
            if ($this->divisionFilter) {
                $this->divisionId = $this->divisionFilter;
                $query->where('division_id', $this->divisionFilter);
            }

            if (!empty($this->typeFilter)) {
                $query->where('speciality_type', $this->typeFilter);
            }
        }

        return $query->paginate(config('pagination.per_page'));
    }

    /**
     * Dispatch next sync jobs for remaining pages.
     *
     * @return void
     * @throws Throwable
     */
    protected function dispatchNextSyncJobs(): void
    {
        $token = Session::get(config('ehealth.api.oauth.bearer_token'));
        $user = Auth::user();

        Bus::batch([new HealthcareServiceSync(legalEntity(), page: 2)])
            ->withOption('division_id', $this->divisionUuid)
            ->withOption('token', Crypt::encryptString($token))
            ->withOption('user', $user)
            ->then(fn () => $user->notify(new SyncNotification('healthcare_service', 'completed')))
            ->catch(function (Batch $batch, Throwable $exception) use ($user) {
                Log::error('Healthcare Service sync batch failed.', [
                    'batch_id' => $batch->id,
                    'exception' => $exception
                ]);

                $user->notify(new SyncNotification('healthcare_service', 'failed'));
            })
            ->onQueue('sync')
            ->name('HealthcareServiceSync')
            ->dispatch();
    }

    public function render(): View
    {
        return view('livewire.division.healthcare-service.healthcare-service-index', [
            'healthcareServices' => $this->healthcareServices
        ]);
    }
}
