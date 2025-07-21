<?php

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\Api\EmployeeApi;
use App\Enums\Status;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Repositories\EmployeeRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

#[AllowDynamicProperties]
class EmployeeIndex extends EmployeeComponent
{
    use WithPagination;

    // --- Component State for Filters ---
    public string $search = '';

    // Status filter is now an array for multi-select, pre-filled with defaults.
    public array $status = ['APPROVED', 'NEW', 'DISMISSED'];

    public array $filter = [
        'phone' => '',
        'email' => '',
        'role' => '',
        'position' => '',
    ];

    // --- State for Modals ---
    public bool $showDismissModal = false;
    public ?int $employeeToDismissId = null;
    public ?string $employeeToDismissName = null;

    public bool $showDeleteModal = false;
    public ?int $requestToDeleteId = null;
    public ?string $deleteRequestName = null;
    public string $deleteRequestText = '';

    public bool $canViewEmployeeDetails, $canUpdateEmployee, $canDismissEmployee;
    public bool $canViewEmployeeRequest, $canUpdateEmployeeRequest, $canDeleteEmployeeRequest, $canCreateRequest;

    private LegalEntity $legalEntity;

    /**
     * Boot the component, load dictionaries, and EAGER LOAD permissions.
     * This is the most efficient way to handle permissions for the list.
     */
    public function boot(): void
    {
        $this->legalEntity = legalEntity();
        $this->loadDictionaries();

        $user = auth()->user();
        $userPermissions = $user?->getPermissionsViaRoles()->pluck('name');

        $this->canViewEmployeeDetails = $userPermissions->contains('employee:details');
        $this->canUpdateEmployee = $userPermissions->contains('employee:write');
        $this->canDismissEmployee = $userPermissions->contains('employee:deactivate');
        $this->canViewEmployeeRequest = $userPermissions->contains('employee_request:read');
        $this->canUpdateEmployeeRequest = $userPermissions->contains('employee_request:write');
        $this->canDeleteEmployeeRequest = $userPermissions->contains('employee_request:write');
        $this->canCreateRequest = $userPermissions->contains('employee_request:write');
    }


    /**
     * Reset pagination when filters or search are updated.
     */
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

        // --- Step 1: Build the base query with all filters to get only the IDs ---
        $query = Party::query()
            ->where(function ($q) use ($legalEntityId) {
                $q->whereHas('employees', fn($sub) => $sub->where('legal_entity_id', $legalEntityId))
                    ->orWhereHas('employeeRequests', fn($sub) => $sub->where('legal_entity_id', $legalEntityId));
            });

        // Apply Status Filter
        if (!empty($this->status)) {
            $query->where(function ($q) {
                $employeeStatuses = array_intersect($this->status, ['APPROVED', 'DISMISSED']);
                if (!empty($employeeStatuses)) {
                    $q->orWhereHas('employees', fn($sub) => $sub->whereIn('status', $employeeStatuses));
                }
                if (in_array('NEW', $this->status, true)) {
                    $q->orWhereHas('employeeRequests');
                }
            });
        } else {
            $query->whereRaw('1=0');
        }

        // Apply Name Search
        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('last_name', 'ilike', "%{$this->search}%")
                    ->orWhere('first_name', 'ilike', "%{$this->search}%")
                    ->orWhere('second_name', 'ilike', "%{$this->search}%");
            });
        }

        // Apply Advanced Filters
        if (!empty($this->filter['phone'])) {
            $query->whereHas('phones', fn($q) => $q->where('number', 'like', '%' . $this->filter['phone'] . '%'));
        }
        if (!empty($this->filter['email'])) {
            $query->where('email', 'ilike', '%' . $this->filter['email'] . '%');
        }
        if (!empty($this->filter['role'])) {
            $query->where(function($q) {
                $q->whereHas('employees', fn($sub) => $sub->where('employee_type', $this->filter['role']))
                    ->orWhereHas('employeeRequests', fn($sub) => $sub->where('employee_type', 'like', $this->filter['role']));
            });
        }
        if (!empty($this->filter['position'])) {
            $query->where(function($q) {
                $q->whereHas('employees', fn($sub) => $sub->where('position', $this->filter['position']))
                    ->orWhereHas('employeeRequests', fn($sub) => $sub->where('position', 'like', $this->filter['position']));
            });
        }

        // --- Step 2: Get the paginated result of IDs. This is fast. ---
        $paginator = $query->paginate(10);

        // --- Step 3: Now, get the full models for the current page with all relationships. ---
        $partiesOnPage = Party::whereIn('id', $paginator->pluck('id')->all())
            ->with([
                       'phones',
                       'employees' => fn($q) => $q->where('legal_entity_id', $legalEntityId)->with('division'),
                       'employeeRequests' => fn($q) => $q->where('legal_entity_id', $legalEntityId)->with('division'),
                   ])
            ->get()
            ->sortBy(function ($party) use ($legalEntityId) {
                $hasRequests = $party->employeeRequests->isNotEmpty();
                $hasActiveEmployees = $party->employees->where('status', 'APPROVED')->isNotEmpty();

                if ($hasRequests) return 1; // Drafts first
                if ($hasActiveEmployees) return 2; // Active employees second
                return 3; // Dismissed employees last
            });

        // --- Step 4: Return a new paginator instance with the sorted items. ---
        return new LengthAwarePaginator(
            $partiesOnPage,
            $paginator->total(),
            $paginator->perPage(),
            $paginator->currentPage(),
            ['path' => $paginator->path()]
        );
    }

    /**
     * Prepares and shows the dismissal modal.
     */
    public function showModalDismissed(int $id): void
    {
        $employee = Employee::with('party')->find($id);
        if (!$employee) return;

        $this->employeeToDismissName = $employee->party->fullName ?? 'співробітника';
        $this->employeeToDismissId = $id;
        $this->showDismissModal = true;
    }

    /**
     * Closes the dismissal modal and resets its state.
     */
    public function closeModal(): void
    {
        $this->showDismissModal = false;
        $this->reset(['employeeToDismissId', 'employeeToDismissName']);
    }

    public function resetFilters(): void
    {
        $this->reset(['filter', 'status', 'search']);
        $this->status = ['APPROVED', 'NEW', 'DISMISSED'];
        $this->resetPage();
    }

    /**
     * Performs the dismissal action.
     */
    public function dismissed(): void
    {
        $employee = Employee::find($this->employeeToDismissId);
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
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalSuccess'), 'type' => 'success']);
            } else {
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalEhealthError'), 'type' => 'error']);
            }
        } catch (\Exception $e) {
            $this->dispatch('flashMessage', ['message' => __('employees.requestError', ['error' => $e->getMessage()]), 'type' => 'error']);
        }

        $this->closeModal();
    }

    public function syncEmployees(): void
    {
        try {
            $apiResponse = EmployeeRequestApi::getEmployees($this->legalEntity->uuid);

            if (!isset($apiResponse['data']) || !is_array($apiResponse['data'])) {
                $this->dispatch('flashMessage', ['message' => 'Не вдалося отримати список співробітників з E-Health.', 'type' => 'error']);
                return;
            }

            $requests = $apiResponse['data'];

            foreach ($requests as $request) {
                if (!isset($request['id'])) {
                    continue;
                }

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

            $this->dispatch('flashMessage', ['message' => __('employees.sync_success'), 'type' => 'success']);

        } catch (\Exception $e) {

            Log::error('Employee sync failed: ' . $e->getMessage());
            $this->dispatch('flashMessage', ['message' => __('employees.requestError', ['error' => 'Помилка синхронізації']), 'type' => 'error']);
        }
    }

    private function dispatchErrorMessage(string $message, string $type = 'success', array $errors = []): void
    {
        $this->dispatch('show-notification', [
            'message' => $message, 'type' => $type, 'errors' => $errors
        ]);
    }

    public function confirmRequestDeletion(int $id): void
    {
        $request = EmployeeRequest::with('party')->find($id);
        if (!$request || $request->uuid) {
            return;
        }
        $this->requestToDeleteId = $id;
        $this->deleteRequestName = $request->party->fullName ?? 'співробітника';
        $this->deleteRequestText = 'Ви впевнені, що хочете видалити чернетку?';
        $this->showDeleteModal = true;
    }

    public function deleteRequest(): void
    {
        $request = EmployeeRequest::find($this->requestToDeleteId);
        if ($request && !$request->uuid) {
            $request->delete();
            $this->dispatch('flashMessage', ['message' => 'Чернетку успішно видалено.', 'type' => 'success']);
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
