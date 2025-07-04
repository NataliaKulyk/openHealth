<?php

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\Api\EmployeeApi;
use App\Enums\Status;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\BaseEmployee;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Repositories\EmployeeRepository;
use App\Traits\FormTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[AllowDynamicProperties]
class EmployeeIndex extends Component
{
    use FormTrait;
    use WithPagination;

    public Collection $employees;
//    public Collection $divisions;


    public string $status = '';
    public array $filter = [
        'phone' => '',
        'email' => '',
        'role' => '',
        'position' => '',
        'division_id' => '',
    ];

    public string $search = '';

    public string $dismiss_text = '';
    public int $dismissed_id = 0;
    public ?string $dismissal_employee_name = null;
    public ?string $deleteRequestName = null;
    public string $deleteRequestText = '';
    public bool $showDeleteModal   = false;
    public ?int $requestToDeleteId = null;
    public ?string $employeeCacheKey = null;


    private LegalEntity $legalEntity;

    public array $dictionaryNames = [
        'POSITION', 'EMPLOYEE_TYPE', 'GENDER'
    ];

    public function boot(): void
    {
        $this->legalEntity = legalEntity();
    }

    public function mount(LegalEntity $legalEntity): void
    {
        $this->getDictionary();
//        $this->divisions = $this->legalEntity->divisions()->get();
        $this->employees = new Collection();
        $this->employeeCacheKey = 'employees_cache_' . $this->legalEntity->id;
    }

    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        $legalEntityId = $this->legalEntity->id;

        $query = Party::query()
            ->where(function ($q) use ($legalEntityId) {
                $q->whereHas('employees', fn($subq) => $subq->where('legal_entity_id', $legalEntityId))
                    ->orWhereHas('employeeRequests', fn($subq) => $subq->where('legal_entity_id', $legalEntityId));
            })
            ->with([
                       'phones',
                       'employees' => fn($q) => $q->where('legal_entity_id', $legalEntityId)->with('division'),
                       'employeeRequests' => fn($q) => $q->where('legal_entity_id', $legalEntityId)->with('division')
                   ]);

        // Main search by full name
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('last_name', 'ilike', "%{$this->search}%")
                    ->orWhere('first_name', 'ilike', "%{$this->search}%")
                    ->orWhere('second_name', 'ilike', "%{$this->search}%");
            });
        }

        // --- Applying new filters ---
        if (!empty($this->status)) {
            $query->whereHas('employees', fn($q) => $q->where('status', $this->status));
        }
        if (!empty($this->filter['phone'])) {
            $query->whereHas('phones', fn($q) => $q->where('number', 'like', '%' . $this->filter['phone'] . '%'));
        }
        if (!empty($this->filter['email'])) {
            $query->where('email', 'ilike', '%' . $this->filter['email'] . '%');
        }
        if (!empty($this->filter['role'])) {
            $query->whereHas('employees', fn($q) => $q->where('employee_type', $this->filter['role']));
        }
        if (!empty($this->filter['position'])) {
            $query->whereHas('employees', fn($q) => $q->where('position', $this->filter['position']));
        }
//        if (!empty($this->filter['division_id'])) {
//            $query->whereHas('employees', fn($q) => $q->where('division_id', $this->filter['division_id']));
//        }
        // --- End of new filters ---

        $paginator = $query->paginate(10);

        $paginator->getCollection()->transform(function ($party) {
            $party->employees->each(fn($p) => $p->type = 'employee');
            $party->employeeRequests->each(fn($p) => $p->type = 'request');
            return $party;
        });

        return $paginator;
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status']) || str_starts_with($property, 'filter.')) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['filter', 'status', 'search']);
        $this->resetPage();
    }

    /**
     * Prepares and shows the dismissal modal.
     */
    public function showModalDismissed(int $id): void
    {
        $employee = Employee::find($id);
        if (!$employee) return;

        $this->dismissal_employee_name = $employee->fullName;
        $this->dismiss_text =  __('employees.dismissalWarning');

        $this->dismissed_id = $employee->id;

        $this->openModal();
    }

    #[On('refreshPage')]
    public function refreshPage()
    {
        $this->dispatch('$refresh');
    }

    public function tableHeaders(): void
    {
        $this->tableHeaders = [
            __('ID E-health '),
            __('ПІБ'),
            __('Телефон'),
            __('Email'),
            __('Посада'),
            __('Статус'),
            __('forms.action'),
        ];
    }

    public function sortEmployees($status): void
    {
        $this->status = $status;
        $this->getEmployees();
    }

    public function dismissed(int $employeeId): void
    {
        $employee = Employee::find($employeeId);
        if (!$employee) {
            $this->closeModal();
            return;
        }

        try {
            $response = EmployeeRequestApi::dismissedEmployeeRequest($employee->uuid);

            if (!empty($response)) {
                $employee->update(
                    [
                        'status'   => Status::DISMISSED->value,
                        'end_date' => Carbon::now()->format('Y-m-d'),
                    ]
                );

                $this->dispatchErrorMessage(__('employees.dismissalSuccess'), 'success');
            } else {
                $this->dispatchErrorMessage(__('employees.dismissalEhealthError'));
            }
        } catch (\Exception $e) {
            $this->dispatchErrorMessage(
                __('employees.requestError', ['error' => $e->getMessage()])
            );
        }

        $this->closeModal();
    }

    //TODO: Створити багато співробітників в статусі не підтверджено, створювати таблицю EmployeeRequest? перевірити Rate Limit
    public function getEmployeeRequestsList()
    {
        return EmployeeRequestApi::getEmployeeRequestsList();
    }

    /**
     * Syncs employees by fetching data from the EmployeeRequestApi and saving it using the employeeSyncService.
     *
     * @throws \Exception
     */
    public function getLastStoreId(): void
    {
        if (Cache::has($this->employeeCacheKey) && !empty(Cache::get($this->employeeCacheKey)) && is_array(Cache::get($this->employeeCacheKey))) {
            $this->storeId = array_key_last(Cache::get($this->employeeCacheKey));
        }
        $this->storeId++;
    }

    public function getEmployeesCache(): Collection
    {
        if (Cache::has($this->employeeCacheKey)) {
            return collect(Cache::get($this->employeeCacheKey))->map(function ($data) {
                $employee = new BaseEmployee()->forceFill($data['party']);
                $employee->party = new Party()->forceFill($data['party'] ?? []);
                $employee->party->phones = new Phone()->forceFill($data['party']['phones'] ?? []);
                return $employee;
            });
        }
        return collect();
    }

    public function getEmployees(): void
    {
        if ($this->status === 'APPROVED') {
            $this->employees = $this->legalEntity->employees()->get();
        } elseif ($this->status === 'NEW') {
            $this->employees = $this->legalEntity->employeesRequest()->get();
        } else {
            $this->employees = $this->getEmployeesCache();
        }
    }

    public function syncEmployees(): void
    {
        $requests = EmployeeRequestApi::getEmployees($this->legalEntity->uuid);
        foreach ($requests as $request) {
            $response = EmployeeRequestApi::getEmployeeById($request['id']);
            $employeeResponse = schemaService()->setDataSchema($response, app(EmployeeApi::class))
                ->responseSchemaNormalize()
                ->replaceIdsKeysToUuid(['id', 'legalEntityId', 'divisionId', 'partyId'])
                ->snakeCaseKeys(true)
                ->getNormalizedData();
            app(EmployeeRepository::class)
                ->store($employeeResponse,
                        legalEntity(),
                        new Employee());
        }

        $this->dispatchErrorMessage(__('Співробітники успішно синхронізовано'));

        $this->getEmployees();
    }

    /**
     * Shows a confirmation modal before deleting an employee request.
     */
    public function confirmRequestDeletion(int $id): void
    {
        // We need to load the party relation to get the name
        $request = EmployeeRequest::with('party')->find($id);

        // Security check: ensure we only delete drafts without a UUID
        if ($request && !$request->uuid) {
            $this->requestToDeleteId = $id;

            $this->deleteRequestName = $request->party->fullName ?? 'співробітника';
            $this->deleteRequestText = 'Ви впевнені, що хочете видалити чернетку для цього співробітника? Цю дію неможливо буде скасувати.';

            $this->showDeleteModal = true;
        } else {
            $this->dispatchErrorMessage(__('Цей запит не є чернеткою і не може бути видалений.'), 'error');
        }
    }

    /**
     * Deletes the employee request from the database.
     */
    public function deleteRequest(): void
    {
        $request = EmployeeRequest::find($this->requestToDeleteId);

        // Final security check
        if ($request && !$request->uuid) {
            $request->delete();
            $this->dispatchErrorMessage(__('Чернетку успішно видалено.'), 'success');
        }

        $this->showDeleteModal = false;
        $this->requestToDeleteId = null;
    }


    private function dispatchErrorMessage(string $message, string $type = 'success', array $errors = []): void
    {
        $this->dispatch('flashMessage', [
            'message' => $message,
            'type'    => $type,
            'errors'  => $errors
        ]);
    }

    public function render(): object
    {
        return view('livewire.employee.employee-index', [
            'parties' => $this->parties,
        ]);
    }
}
