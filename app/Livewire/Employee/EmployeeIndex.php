<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\Api\EmployeeApi;
use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Status;
use App\Exceptions\EHealth\EHealthException;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Repositories\EmployeeRepository;
use App\Repositories\Repository;
use Illuminate\Bus\Batch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use function Laravel\Prompts\error;

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
    public bool $showDeactivateModal    = false;
    public ?int $employeeToDeactivateId   = null;
    public ?string $employeeToDeactivateName = null;

    public bool $showDeleteModal = false;
    public ?int $requestToDeleteId = null;
    public ?string $deleteRequestName = null;

    private LegalEntity $legalEntity;

    /**
     * Boot the component, load dictionaries, and EAGER LOAD permissions.
     * This is the most efficient way to handle permissions for the list.
     */
    public function boot(): void
    {
        $this->legalEntity = legalEntity();
        $this->loadDictionaries();
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
                // Use the Status enum for the filter values
                $employeeStatuses = array_intersect($this->status, [Status::APPROVED->value, Status::DISMISSED->value]);
                if (!empty($employeeStatuses)) {
                    $q->orWhereHas('employees', fn($sub) => $sub->whereIn('status', $employeeStatuses));
                }
                // Use the Status enum for the 'NEW' case
                if (in_array(Status::NEW->value, $this->status, true)) {
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
            ->sortBy(function ($party) {
                // Use the Status enum for the sorting logic as well
                $hasActiveEmployees = $party->employees->where('status', Status::APPROVED->value)->isNotEmpty();
                $hasRequests = $party->employeeRequests->isNotEmpty();

                // Prioritize Active Employees first
                if ($hasActiveEmployees) {
                    return 1;
                }
                // Then, drafts (requests)
                if ($hasRequests) {
                    return 2;
                }
                // Finally, dismissed employees
                return 3;
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
     * Prepares and shows the deactivation modal.
     */
    public function showModalDeactivate(int $id): void
    {
        $employee = Employee::with('party')->find($id);
        if (!$employee) return;

        $this->employeeToDeactivateName = $employee->party->fullName ?? __('employees.modals.deactivate.default_name');
        $this->employeeToDeactivateId   = $id;
        $this->showDeactivateModal    = true;
    }

    /**
     * Closes the dismissal modal and resets its state.
     */
    public function closeModal(): void
    {
        $this->showDeactivateModal = false;
        $this->reset(['employeeToDismissId', 'employeeToDismissName']);
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

    /**
     * @throws ConnectionException
     */
    public function sync(): void
    {
        try {
            $response = EHealth::employee()->getMany(['legal_entity_id' => legalEntity()->uuid]);
        } catch (ConnectionException $e) {
            Log::error('Employee sync failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('flashMessage',  ['message' => __('errors.ehealth.messages.no_connection'), 'type' => 'error']);
            return;
        } catch (EHealthResponseException $e) {
            Log::error('Employee sync failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('flashMessage',  ['message' => __('employees.requestError', ['error' => $e->getMessage()]), 'type' => 'error']);
            return;
        }

        $employees = $response->validate();
        data_forget($employees, '*.party');
        data_forget($employees, '*.doctor');

        Employee::upsert($employees, uniqueBy: ['uuid']);

        /**
         * Employees are updated, now we need to proccess all related data, i.e., party, documents, phones, educations, specialties, etc.
         * This is done through the Employee Details endpoint, with a cooldown to respect rate limits.
         * We use batching to handle this efficiently.
         */

        // First get all saved employee models
        $models = Employee::with('party')->filterByUuids(array_column($employees, 'uuid'))->get();

        $model = $models->find(33);

        $employee = EHealth::employee()->getDetails($model->uuid);
        $employee = $employee->validate();

        $doctor = Arr::pull($employee, 'doctor', []);
        $educations = Arr::pull($doctor, 'educations', []);
        $specialties = Arr::pull($doctor, 'specialties', []);

        $party = Arr::pull($employee, 'party');
        $partyUuid = Arr::pull($party, 'uuid');
        $documents = Arr::pull($party, 'documents', []);
        $phones = Arr::pull($party, 'phones', []);

        /**
         * The logic behind the party update or create is as follows:
         * 1. Check party by UUID. Possible scenario: the party already exists in the system
         * 2. If user already has a party, update it.
         * 3. If user does not have a party, but there is a party with the same UUID, update it and establish the relation.
         * 4. If neither of the above, create a new party and establish the relation.
         */

        $partyByUuid = Party::where('uuid', $partyUuid)->first();

        // If the model doesn't have a party and party doesn't exist, create new one. It's a brand-new person
        if (!($partyByUuid && $model->party)) {
            $theParty = $model->party()->create($party);

        // If the model doesn't have a related party but the party already exists, update it and relate - the scenario of a new employee with already created person/party
        } else if ($partyByUuid && !$model->party) {
            $theParty = $model->party()->save($partyByUuid);

        // The model already has a related party, update it and change the UUID - the case when eHealth creates another party, probably merge scenario
        } else if (!$partyByUuid && $model->party) {
            $theParty = $model->party()->update(array_merge(
                $party,
                ['uuid' => $partyUuid]
            ));
        // Both the model and the party exist, check if they are the same
        } else if ($partyByUuid && $model->party) {

            // uuid is the same, just update
            if ($partyByUuid->uuid == $model->party->uuid) {
                $theParty = $model->party()->update($partyByUuid);
            } else {

            // Different uuid, need to merge the results, prioritizing the eHealth data
                $result = array_merge(
                    $model->party()->toArray(),
                    $partyByUuid->toArray()
                );

                $theParty = $model->party()->update($result);
            }
        }

        dd($theParty->toArray());
    }

    /**
     * Fetches the list of employees from the E-Health API.
     *
     * @return array
     */
    private function getRemoteEmployees(): array
    {
        $apiResponse = EmployeeRequestApi::getEmployees($this->legalEntity->uuid);

        return isset($apiResponse['data']) && is_array($apiResponse['data'])
            ? $apiResponse['data']
            : [];
    }

    /**
     * Gets all existing employee UUIDs from the local database for the current legal entity.
     *
     * @return array
     */
    private function getLocalEmployeeUuids(): array
    {
        return Employee::where('legal_entity_id', $this->legalEntity->id)
            ->pluck('uuid') // Select only the 'uuid' column
            ->all();      // Convert the collection to a simple array
    }

    /**
     * Iterates over new employee IDs, fetches their full data, and stores them locally.
     *
     * @param array $newEmployeeIds
     * @return int The number of successfully added employees.
     */
    private function createNewEmployees(array $newEmployeeIds): int
    {
        $successfullyAddedCount = 0;
        $employeeRepository = app(EmployeeRepository::class);
        $employeeApi = app(EmployeeApi::class);

        foreach ($newEmployeeIds as $employeeId) {
            try {
                // This is the unavoidable N+1 API call for details.
                $fullEmployeeData = EmployeeRequestApi::getEmployeeById($employeeId);
                if (empty($fullEmployeeData)) {
                    Log::warning("Could not fetch details for employee ID: {$employeeId}");
                    continue;
                }

                // Normalize and prepare data for storing.
                $employeeResponse = schemaService()->setDataSchema($fullEmployeeData, $employeeApi)
                    ->responseSchemaNormalize()
                    ->replaceIdsKeysToUuid(['id', 'legalEntityId', 'divisionId', 'partyId'])
                    ->snakeCaseKeys(true)
                    ->getNormalizedData();

                // Store the new employee.
                $employeeRepository->store($employeeResponse, legalEntity(), new Employee());

                $successfullyAddedCount++;
            } catch (\Exception $e) {
                // Log the specific error and continue with the next employee.
                // This makes the sync process more resilient.
                Log::error("Failed to create employee with UUID {$employeeId}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $successfullyAddedCount;
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
