<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\EHealth;
use App\Enums\Status;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Jobs\EmployeeDetailsUpsert;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Notifications\EmployeeSyncCompleted;
use Illuminate\Bus\Batch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[AllowDynamicProperties]
class EmployeeIndex extends EmployeeComponent
{
    use WithPagination;

    // --- Component State for Filters ---
    public string $search = '';
    public array $status = ['APPROVED', 'NEW', 'DISMISSED'];
    public array $filter = [
        'phone' => '',
        'email' => '',
        'role' => '',
        'position' => '',
    ];

    // --- State for Modals ---
    public bool $showDeactivateModal = false;
    public ?int $employeeToDeactivateId = null;
    public ?string $employeeToDeactivateName = null;

    public ?int $employeeToDismissId = null;
    public ?string $employeeToDismissName = null;

    public bool $showDeleteModal = false;
    public ?int $requestToDeleteId = null;
    public ?string $deleteRequestName = null;

    public ?string $batchId = null;

    private LegalEntity $legalEntity;

    public function boot(): void
    {
        $this->legalEntity = legalEntity();
    }

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntity = $legalEntity;
        $this->loadDivisions($legalEntity);
        $this->loadDictionaries();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status']) || str_starts_with($property, 'filter.')) {
            $this->resetPage();
        }
    }

    /**
     * Main computed property to fetch and filter parties.
     */
    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        $legalEntityId = $this->legalEntity->id;

        $employeeConstraints = function ($query) {
            $employeeStatuses = array_intersect($this->status, [Status::APPROVED->value, Status::DISMISSED->value]);

            if (empty($employeeStatuses)) {
                return $query->whereRaw('1=0');
            }

            $query->whereIn('status', $employeeStatuses);

            if (!empty($this->filter['division_id'])) {
                $query->where('division_id', $this->filter['division_id']);
            }
            if (!empty($this->filter['role'])) {
                $query->where('employee_type', $this->filter['role']);
            }
            if (!empty($this->filter['position'])) {
                $query->where('position', $this->filter['position']);
            }
        };

        $requestConstraints = function ($query) {
            if (!in_array(Status::NEW->value, $this->status, true)) {
                return $query->whereRaw('1=0');
            }

            $query->whereNull('applied_at')
                ->whereIn('status', [Status::NEW->value, Status::SIGNED->value]);

            if (!empty($this->filter['division_id'])) {
                $query->where('division_id', $this->filter['division_id']);
            }
            if (!empty($this->filter['role'])) {
                $query->where('employee_type', 'like', $this->filter['role']);
            }
            if (!empty($this->filter['position'])) {
                $query->where('position', 'like', $this->filter['position']);
            }
        };

        $query = Party::query();

        $query->where(function ($q) use ($legalEntityId) {
            $q->whereHas('employees', fn ($sub) => $sub->where('legal_entity_id', $legalEntityId))
                ->orWhereHas('employeeRequests', fn ($sub) => $sub->where('legal_entity_id', $legalEntityId));
        });

        if (!empty($this->search)) {
            $query->where(fn ($q) => $q->where('last_name', 'ilike', "%{$this->search}%")
                ->orWhere('first_name', 'ilike', "%{$this->search}%")
                ->orWhere('second_name', 'ilike', "%{$this->search}%"));
        }
        if (!empty($this->filter['phone'])) {
            $query->whereHas('phones', fn ($q) => $q->where('number', 'like', '%' . $this->filter['phone'] . '%'));
        }
        if (!empty($this->filter['email'])) {
            $query->where('email', 'ilike', '%' . $this->filter['email'] . '%');
        }

        if (!empty($this->status)) {
            $query->where(function ($q) use ($employeeConstraints, $requestConstraints) {
                $q->whereHas('employees', $employeeConstraints)
                    ->orWhereHas('employeeRequests', $requestConstraints);
            });
        } else {
            $query->whereRaw('1=0');
        }

        $paginator = $query->select('id')->paginate(10);

        $partiesOnPage = Party::whereIn('id', $paginator->pluck('id')->all())
            ->with([
                       'phones',
                       'employees' => function ($query) use ($legalEntityId, $employeeConstraints) {
                           $query->where('legal_entity_id', $legalEntityId)->with('division');
                           $employeeConstraints($query);
                       },
                       'employeeRequests' => function ($query) use ($legalEntityId, $requestConstraints) {
                           $query->where('legal_entity_id', $legalEntityId)->with('division');
                           $requestConstraints($query);
                       },
                   ])
            ->get()
            ->filter(fn ($party) => $party->employees->isNotEmpty() || $party->employeeRequests->isNotEmpty())
            ->sortBy(function ($party) {
                $hasActiveEmployees = $party->employees->isNotEmpty();
                $hasRequests = $party->employeeRequests->isNotEmpty();
                if ($hasActiveEmployees) {
                    return 1;
                }
                if ($hasRequests) {
                    return 2;
                }

                return 3;
            });

        return new LengthAwarePaginator(
            $partiesOnPage,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => $paginator->path()]
        );
    }

    public function showModalDeactivate(int $id): void
    {
        $employee = Employee::with('party')->find($id);
        if (!$employee) {
            return;
        }

        $this->employeeToDeactivateName = $employee->party->fullName ?? __('employees.modals.deactivate.default_name');
        $this->employeeToDeactivateId = $id;
        $this->showDeactivateModal = true;
    }

    /**
     * Closes the dismissal modal and resets its state.
     */
    public function closeModal(): void
    {
        $this->showDeactivateModal = false;
        $this->reset(['employeeToDeactivateId', 'employeeToDeactivateName']);
    }

    public function resetFilters(): void
    {
        $this->reset(['filter', 'status', 'search']);
        $this->status = ['APPROVED', 'NEW', 'DISMISSED'];
        $this->resetPage();
    }

    /**
     * Performs the deactivation action.
     */
    public function deactivate(): void
    {
        $employee = Employee::find($this->employeeToDeactivateId);
        if (!$employee) {
            $this->closeModal();

            return;
        }

        try {
            $response = EmployeeRequestApi::dismissedEmployeeRequest($employee->uuid);

            if (!empty($response)) {
                $employee->update(
                    [
                        'status' => Status::DISMISSED->value,
                        'end_date' => Carbon::now()->format('Y-m-d'),
                    ]
                );
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalSuccess'), 'type' => 'success']);
            } else {
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalEhealthError'), 'type' => 'error']);
            }
        } catch (\Exception $e) {
            $this->dispatch('flashMessage', ['message' => __('employees.requestError', ['error' => $e->getMessage()]), 'type' => 'error']);
        }

        $this->closeModal();
    }

    public function sync(): void
    {
        try {
            $response = EHealth::employee()->getMany(['legal_entity_id' => legalEntity()->uuid]);
        } catch (ConnectionException $e) {
            Log::error('Employee sync failed: No connection to E-Health.', ['error' => $e->getMessage()]);
            $this->dispatch('flashMessage', ['message' => __('errors.ehealth.messages.no_connection'), 'type' => 'error']);

            return;
        } catch (EHealthResponseException $e) {
            Log::error('Employee sync failed: E-Health API error.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('flashMessage', ['message' => __('employees.requestError', ['error' => $e->getMessage()]), 'type' => 'error']);

            return;
        } catch (\Exception $e) {
            Log::error('Employee sync failed: An unexpected error occurred during initiation.', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('flashMessage', ['message' => __('employees.sync.error'), 'type' => 'error']);

            return;
        }

        $employees = $response->validate();
        data_forget($employees, '*.party');
        data_forget($employees, '*.doctor');
        data_fill($employees, '*.legal_entity_id', legalEntity()->id);

        Employee::upsert($employees, uniqueBy: ['uuid']);
        $models = Employee::with('party')->filterByUuids(array_column($employees, 'uuid'))->get();

        $user = Auth::user();

        $batch = Bus::batch(
            $models->map(fn (Employee $model) => new EmployeeDetailsUpsert(
                $model,
                $user,
                session()->get(config('ehealth.api.oauth.bearer_token'))
            ))
        )->then(function (Batch $batch) use ($user) {
            $message = __('employees.sync.completed_successfully', [
                'processed' => $batch->processedJobs,
                'total' => $batch->totalJobs,
            ]);
            $user->notify(new EmployeeSyncCompleted($message, 'success'));

        })->catch(callback: function (Batch $batch, \Throwable $e) use ($user) {
            $message = __('employees.sync.failed');
            $user->notify(new EmployeeSyncCompleted($message, 'error'));
            Log::error('Employee sync batch failed.', [
                'batch_id' => $batch->id,
                'exception' => $e
            ]);
        })
            ->name('Employee Full Sync')
            ->dispatch();

        $this->batchId = $batch->id;
        $this->dispatch('flashMessage', [
            'message' => __('employees.sync.started'),
            'type' => 'success'
        ]);
    }

    public function confirmRequestDeletion(int $id): void
    {
        $request = EmployeeRequest::with('party')->find($id);
        if (!$request || $request->uuid) {
            return;
        }
        $this->requestToDeleteId = $id;
        $this->deleteRequestName = $request->party->fullName ?? __('employees.modals.delete_draft.default_name');
        $this->showDeleteModal = true;
    }

    public function deleteRequest(): void
    {
        $request = EmployeeRequest::find($this->requestToDeleteId);
        if ($request && !$request->uuid) {
            $request->delete();
            $this->dispatch('flashMessage', ['message' => __('employees.draft.delete_success'), 'type' => 'success']);
        }
        $this->showDeleteModal = false;
        $this->requestToDeleteId = null;
    }

    /**
     * Renders the component view.
     */
    public function render(): object
    {
        return view('livewire.employee.employee-index', [
            'parties' => $this->parties,
            'dictionaries' => $this->dictionaries,
        ]);
    }
}
