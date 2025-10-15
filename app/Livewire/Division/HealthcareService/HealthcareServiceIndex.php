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

    public array $dictionaryNames = ['DIVISION_TYPE', 'SPECIALITY_TYPE', 'PROVIDING_CONDITION'];

    public function mount(LegalEntity $legalEntity, Division $division): void
    {
        $this->divisionId = $division->id;
        $this->divisionUuid = $division->uuid;
        $this->divisionStatus = $division->status;

        $this->getDictionary();
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

    public function delete($id): void
    {
        $healthcareService = HealthcareService::select(['id', 'legal_entity_id', 'status'])
            ->findOrFail($id);

        if (Auth::user()?->cannot('delete', $healthcareService)) {
            Session::flash('error', 'У вас немає дозволу на видалення заявки на створення послуги');

            return;
        }

        try {
            HealthcareService::destroy($id);

            Session::flash('success', 'Чернетку послуги успішно видалено');
        } catch (Exception $exception) {
            $this->logDatabaseErrors($exception, 'Error while deleting healthcare service: ');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function sync(): void
    {
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
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');
            $this->logDatabaseErrors($exception, 'Error while synchronizing healthcare services with eHealth: ');

            return;
        }

        // If there are more pages, dispatch a job to handle the rest
        if ($response->isNotLast()) {
            try {
                $this->dispatchNextSyncJobs();
                Session::flash('success', __('Синхронізацію успішно розпочато.'));
            } catch (Throwable $exception) {
                Log::error('Failed to dispatch HealthcareServiceSync batch', [
                    'exception' => $exception
                ]);

                Auth::user()?->notify(new SyncNotification('healthcare_service', 'failed'));
            }
        } else {
            Session::flash('success', __('Інформацію успішно оновлено'));
        }
    }

    #[Computed]
    public function healthcareServices(): LengthAwarePaginator
    {
        $query = HealthcareService::with('division:id,name,status')
            ->select(
                [
                    'id',
                    'uuid',
                    'division_id',
                    'speciality_type',
                    'providing_condition',
                    'ehealth_inserted_at',
                    'status',
                    'created_at'
                ]
            )
            ->where('legal_entity_id', legalEntity()->id)
            ->orderByDesc('ehealth_inserted_at')
            ->orderByDesc('created_at');

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
            ->then(fn () => $user->notify(new SyncNotification('healthcare_service', 'complete')))
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
