<?php

declare(strict_types=1);

namespace App\Livewire\EmployeeRole;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Jobs\EmployeeRoleSync;
use App\Models\EmployeeRole;
use App\Models\LegalEntity;
use App\Notifications\SyncNotification;
use App\Repositories\Repository;
use App\Traits\FormTrait;
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

class EmployeeRoleIndex extends Component
{
    use WithPagination;
    use FormTrait;

    /**
     * Full name of employee.
     *
     * @var string
     */
    public string $employeeSearch = '';

    /**
     * Chosen speciality type for filter.
     *
     * @var string|null
     */
    public ?string $specialityTypeFilter = null;

    /**
     * Statuses by default.
     *
     * @var array|string[]
     */
    public array $statusFilter = ['ACTIVE'];

    /**
     * List of all speciality types.
     *
     * @var array
     */
    public array $healthcareServiceSpecialityTypes;

    protected array $dictionaryNames = ['SPECIALITY_TYPE', 'PROVIDING_CONDITION'];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->getDictionary();

        $this->healthcareServiceSpecialityTypes = array_keys($this->dictionaries['SPECIALITY_TYPE']);
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->employeeSearch = '';
        $this->specialityTypeFilter = null;
        $this->statusFilter = ['ACTIVE', 'INACTIVE'];
    }

    public function deactivate(EmployeeRole $employeeRole): void
    {
        $employeeRole->loadMissing('healthcareService:id,legal_entity_id');

        if (Auth::user()->cannot('deactivate', $employeeRole)) {
            Session::flash('error', 'У вас немає дозволу на деактивування ролі');

            return;
        }

        try {
            $response = EHealth::employeeRole()->deactivate($employeeRole->uuid);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, "Error connecting when deactivating $employeeRole->uuid employee role");
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, "Error when deactivating $employeeRole->uuid employee role");

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        }

        try {
            Repository::employeeRole()->update($employeeRole->uuid, $response->validate());

            $this->dispatch('deactivate-success');
            Session::flash('success', 'Роль успішно деактивовано');
        } catch (Throwable $exception) {
            $this->logDatabaseErrors($exception, "Failed to deactivate $employeeRole->uuid employee role");
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    public function sync(): void
    {
        try {
            $response = EHealth::employeeRole()->getMany();
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error connecting when getting a employee role list');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error connecting when getting a employee role list');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        try {
            $validated = $response->validate();
            Repository::employeeRole()->sync($response->map($validated));
        } catch (Throwable $exception) {
            Session::flash('error', 'Виникла помилка. Оновіть список співробітників і послуги та спробуйте ще раз');
            $this->logDatabaseErrors($exception, 'Error while synchronizing employee roles with eHealth: ');

            return;
        }

        // If there are more pages, dispatch a job to handle the rest
        if ($response->isNotLast()) {
            try {
                Auth::user()->notify(new SyncNotification('employee_role', 'started'));
                $this->dispatchNextSyncJobs();
                Session::flash('success', __('Синхронізацію успішно розпочато.'));
            } catch (Throwable $exception) {
                Log::error('Failed to dispatch EmployeeRole batch', ['exception' => $exception]);

                Auth::user()->notify(new SyncNotification('employee_role', 'failed'));
            }
        } else {
            Session::flash('success', __('Інформацію успішно оновлено'));
        }
    }

    #[Computed]
    public function employeeRoles(): LengthAwarePaginator
    {
        return EmployeeRole::forLegalEntity()
            ->filterByEmployeeSearch($this->employeeSearch)
            ->filterBySpecialityType($this->specialityTypeFilter)
            ->filterByStatus($this->statusFilter)
            ->paginate(config('pagination.per_page'));
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

        Bus::batch([new EmployeeRoleSync(legalEntity(), page: 2)])
            ->withOption('token', Crypt::encryptString($token))
            ->withOption('user', $user)
            ->then(fn () => $user->notify(new SyncNotification('employee_role', 'completed')))
            ->catch(function (Batch $batch, Throwable $exception) use ($user) {
                Log::error('Employee Role sync batch failed.', [
                    'batch_id' => $batch->id,
                    'exception' => $exception
                ]);

                $user->notify(new SyncNotification('employee_role', 'failed'));
            })
            ->onQueue('sync')
            ->name('EmployeeRoleSync')
            ->dispatch();
    }

    public function render(): View
    {
        return view('livewire.employee-role.employee-role-index', ['employeeRoles' => $this->employeeRoles]);
    }
}
