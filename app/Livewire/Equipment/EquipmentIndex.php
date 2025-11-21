<?php

declare(strict_types=1);

namespace App\Livewire\Equipment;

use App\Classes\eHealth\EHealth;
use App\Enums\Equipment\AvailabilityStatus;
use App\Enums\Equipment\Status;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Jobs\EquipmentSync;
use App\Livewire\Equipment\Traits\StatusTrait;
use App\Models\Equipment;
use App\Models\LegalEntity;
use App\Notifications\SyncNotification;
use App\Repositories\Repository;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
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

class EquipmentIndex extends Component
{
    use WithPagination;
    use StatusTrait;

    /**
     * Search by equipment name and inventory number.
     *
     * @var string
     */
    public string $searchByName = '';

    /**
     * Search by type from 'device_definition_classification_type' dictionary.
     *
     * @var string|null
     */
    public ?string $typeFilter = null;

    /**
     * List of divisions in the current legal entity.
     *
     * @var array
     */
    public array $divisions;

    /**
     * Search by division ID.
     *
     * @var int|null
     */
    public ?int $divisionFilter = null;

    /**
     * Default values for multiselect filters by statuses.
     *
     * @var array|string[]
     */
    public array $statusFilter;

    /**
     * Default values for multiselect filters by availability statuses.
     *
     * @var array|string[]
     */
    public array $availabilityStatusFilter;

    public bool $isFiltersApplied = false;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->divisions = $legalEntity->divisions()->select(['id', 'name'])->get()->toArray();
        $this->statusFilter = Status::values();
        $this->availabilityStatusFilter = AvailabilityStatus::values();
    }

    public function search(): void
    {
        $this->resetPage();
        $this->isFiltersApplied = true;
    }

    public function resetFilters(): void
    {
        $this->reset();
        $this->statusFilter = Status::values();
        $this->availabilityStatusFilter = AvailabilityStatus::values();
    }

    public function sync(): void
    {
        if (Auth::user()->cannot('sync', Equipment::class)) {
            Session::flash('error', 'У вас немає дозволу на синхронізацію обладнань');

            return;
        }

        try {
            $response = EHealth::equipment()->getMany();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when getting a equipment list');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error connecting when getting a equipment list');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            $validated = $response->validate();
            Repository::equipment()->sync($response->map($validated));
        } catch (Throwable $exception) {
            Session::flash('error', 'Виникла помилка. Оновіть список місць надання послуг та співробітників і спробуйте ще раз');
            $this->logDatabaseErrors($exception, 'Error while synchronizing equipments with eHealth: ');

            return;
        }

        // If there are more pages, dispatch a job to handle the rest
        if ($response->isNotLast()) {
            try {
                Auth::user()->notify(new SyncNotification('equipment', 'started'));
                $this->dispatchNextSyncJobs();
                Session::flash('success', __('forms.success.sync_started'));
            } catch (Throwable $exception) {
                Log::error('Failed to dispatch EquipmentSync batch', ['exception' => $exception]);

                Auth::user()->notify(new SyncNotification('equipment', 'failed'));
            }
        } else {
            Session::flash('success', __('forms.success.updated'));
        }
    }

    #[Computed]
    public function equipments(): LengthAwarePaginator
    {
        $query = Equipment::filterByLegalEntity(legalEntity()->id);

        // Filters
        if ($this->isFiltersApplied) {
            if (!empty($this->searchByName)) {
                $query->where(function (Builder $searchQuery) {
                    $searchQuery->whereHas('names', function (Builder $nameQuery) {
                        $nameQuery->where('name', 'ILIKE', "%$this->searchByName%");
                    })
                        ->orWhere('inventory_number', 'ILIKE', "%$this->searchByName%");
                });
            }

            if (!empty($this->typeFilter)) {
                $query->whereType($this->typeFilter);
            }

            if (!empty($this->divisionFilter)) {
                $query->whereHas('division', function (Builder $query) {
                    $query->where('id', $this->divisionFilter);
                });
            }

            if (!empty($this->statusFilter)) {
                $query->whereIn('status', $this->statusFilter);
            }

            if (!empty($this->availabilityStatusFilter)) {
                $query->whereIn('availability_status', $this->availabilityStatusFilter);
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

        Bus::batch([new EquipmentSync(legalEntity(), page: 2)])
            ->withOption('token', Crypt::encryptString($token))
            ->withOption('user', $user)
            ->then(fn () => $user->notify(new SyncNotification('equipment', 'completed')))
            ->catch(function (Batch $batch, Throwable $exception) use ($user) {
                Log::error('Equipment sync batch failed.', [
                    'batch_id' => $batch->id,
                    'exception' => $exception
                ]);

                $user->notify(new SyncNotification('equipment', 'failed'));
            })
            ->onQueue('sync')
            ->name('EquipmentSync')
            ->dispatch();
    }

    public function render(): View
    {
        return view('livewire.equipment.equipment-index', ['equipments' => $this->equipments]);
    }
}
