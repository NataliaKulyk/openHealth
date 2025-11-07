<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\EHealth;
use App\Enums\JobStatus;
use App\Enums\Status;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Jobs\EmployeeSync;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Models\User;
use App\Notifications\EmployeeSyncCompleted;
use App\Notifications\SyncNotification;
use App\Traits\BatchLegalEntityQueries;
use Illuminate\Bus\Batch;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use JsonException;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Spatie\Permission\PermissionRegistrar;
use Throwable;
use Illuminate\Support\Facades\Crypt;

#[AllowDynamicProperties]
class EmployeeIndex extends EmployeeComponent
{
    use WithPagination;
    use BatchLegalEntityQueries;

    // --- Component State for Filters ---
    public string $search = '';
    public array $status = ['APPROVED', 'NEW'];
    public array $filter = [
        'phone' => '',
        'email' => '',
        'role' => '',
        'position' => '',
        'division_id' => ''
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

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    /**
     * Main computed property to fetch and filter parties.
     */
    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        // === Step 1: Eager load existing Parties that have any relevant positions ===
        $realParties = Party::query()
            // Find parties that have either active employees...
            ->whereHas('employees', fn ($sub) => $sub->where('legal_entity_id', $this->legalEntity->id))
            // ...or have pending/signed requests.
            ->orWhereHas('employeeRequests', fn ($sub) => $sub->where('legal_entity_id', $this->legalEntity->id))
            // Eager load all required relations to prevent N+1 queries and Lazy Loading exceptions
            ->with([
                       'phones',
                       'employees.division',
                       'employees.user',           // For the position's email row
                       'employeeRequests.revision',  // For draft data
                       'employeeRequests.division',
                       'users'                   // For the party header email(s)
                   ])
            ->get();

        // Fetch "pure" drafts (requests without an assigned party_id) that are 'NEW' or 'SIGNED'.
        $unassignedRequests = EmployeeRequest::query()
            ->where('legal_entity_id', $this->legalEntity->id)
            ->whereIn('status', [Status::NEW->value, Status::SIGNED->value])
            ->whereNull('party_id')
            ->with(['revision', 'division'])
            ->get();

        // === Step 2: Transform "pure" drafts into "fake" Party objects ===
        // This allows us to process real Parties and pure drafts in a single, unified list.
        $draftParties = $unassignedRequests->map(function (EmployeeRequest $request) {
            $partyData = $request->revision->data['party'] ?? [];

            $fakeParty = new Party();

            // Manually set name fields so the 'fullName' accessor works for searching.
            $fakeParty->last_name = $partyData['last_name'] ?? null;
            $fakeParty->first_name = $partyData['first_name'] ?? null;
            $fakeParty->second_name = $partyData['second_name'] ?? null;
            $fakeParty->verification_status = 'NOT_VERIFIED'; // Set for verification status filtering.

            // Create a "fake" User relation to handle email filtering consistently.
            $fakeUser = new User();
            $fakeUser->email = $partyData['email'] ?? null;
            $fakeParty->setRelation('users', collect([$fakeUser])->filter(fn ($u) => !empty($u->email)));

            // Set relations to match the real Party structure.
            $fakeParty->id = 'draft_' . $request->id;
            $fakeParty->setRelation('employeeRequests', collect([$request]));
            $fakeParty->setRelation('employees', collect());
            $fakeParty->setRelation('phones', collect());

            return $fakeParty;
        });

        // === Step 3: Merge real Parties and "fake" draft Parties into one list ===
        $allItems = $realParties->merge($draftParties);

        // === Step 4: Apply all filters to the unified collection ===
        $filteredItems = $allItems
            // 4.1. First, filter the "children" (positions) within each Party.
            ->map(function (Party $party) {
                // Use the helper to filter positions based on the current state.
                $filteredEmployees = $party->employees->filter(fn ($pos) => $this->positionMatchesFilters($pos));
                $filteredRequests = $party->employeeRequests->filter(fn ($pos) => $this->positionMatchesFilters($pos));

                $party->setRelation('employees', $filteredEmployees);
                $party->setRelation('employeeRequests', $filteredRequests);

                return $party;
            })
            // 4.2. Second, filter the "parent" Parties themselves.
            ->filter(function (Party $party) {
                // Remove any Party that has no matching positions left after filtering.
                if ($party->employees->isEmpty() && $party->employeeRequests->isEmpty()) {
                    return false;
                }

                // Filter by Full Name (case-insensitive, multi-byte safe).
                if (!empty($this->search) && mb_stripos($party->fullName, $this->search) === false) {
                    return false;
                }

                // Filter by Email (checks all users associated with the party).
                if (!empty($this->filter['email'])) {
                    $emailToSearch = $this->filter['email'];
                    $emailMatches = $party->users->contains(
                        fn ($user) => stripos($user->email, $emailToSearch) !== false
                    );
                    if (!$emailMatches) {
                        return false;
                    }
                }

                // Filter by Phone.
                if (!empty($this->filter['phone'])) {
                    $phoneMatches = $party->phones->contains(fn ($phone) => str_contains($phone->number, $this->filter['phone']));
                    if (!$phoneMatches) {
                        return false;
                    }
                }

                // Filter by verification status (handles 'Verified Only' or 'Not Verified Only')
                if (in_array('VERIFIED', $this->status, true) && !in_array(
                    'NOT_VERIFIED',
                    $this->status,
                    true
                ) && $party->verification_status !== 'VERIFIED') {
                    return false;
                }
                if (!in_array('VERIFIED', $this->status, true) && in_array(
                    'NOT_VERIFIED',
                    $this->status,
                    true
                ) && $party->verification_status === 'VERIFIED') {
                    return false;
                }

                return true;
            });

        // === Step 5: Sort and Paginate the final list ===
        $perPage = 10;
        $currentPage = $this->getPage();

        // Sort by the newest date (either employee or request) to bring most recent activity to the top.
        $sortedItems = $filteredItems->sortByDesc(function (Party $party) {
            $maxEmployeeDate = $party->employees->max('created_at');
            $maxRequestDate = $party->employeeRequests->max('created_at');

            return max($maxEmployeeDate, $maxRequestDate) ?? '1970-01-01 00:00:00';
        });

        $currentPageItems = $sortedItems->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $sortedItems->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * Helper method to check if a single position (Employee or EmployeeRequest)
     * matches the current position-level filters (status, division, etc.).
     */
    private function positionMatchesFilters($position): bool
    {
        // --- 1. Filter by Status ---
        $currentStatuses = $this->status;
        if (!empty($currentStatuses)) {
            // 1.1. Basic check: is the position's status in the selected filter array?
            $statusMatch = $position->status && in_array($position->status->value, $currentStatuses, true);

            // 1.2. Business Logic: If 'NEW' (Draft) is selected, also show 'SIGNED'.
            if (!$statusMatch && $position instanceof EmployeeRequest && in_array(Status::NEW->value, $currentStatuses, true) && $position->status?->value === Status::SIGNED->value) {
                $statusMatch = true;
            }

            // 1.3. Final check: if no status match was found, hide the position.
            if (!$statusMatch) {
                return false;
            }
        }

        // --- 2. Filter by other position attributes ---
        if (!empty($this->filter['division_id']) && $position->division_id !== $this->filter['division_id']) {
            return false;
        }
        if (!empty($this->filter['role']) && $position->employee_type !== $this->filter['role']) {
            return false;
        }
        if (!empty($this->filter['position']) && $position->position !== $this->filter['position']) {
            return false;
        }

        return true; // This position matches all criteria
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
        $this->status = ['APPROVED', 'NEW'];
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

                if ($user = $employee->user) {
                    $roleToRemove = $employee->employee_type;
                    if ($user->hasRole($roleToRemove)) {
                        $user->removeRole($roleToRemove);

                    }
                }

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
     * @throws ContainerExceptionInterface
     * @throws Throwable
     * @throws NotFoundExceptionInterface
     */
    public function sync(): void
    {
        $user = Auth::user();
        $user->notify(new SyncNotification('employee', 'started'));

        $this->dispatch('flashMessage', [
                'message' => __('employees.sync.started'),
                'type' => 'success'
            ]);

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
        data_fill($employees, '*.sync_status', JobStatus::PARTIAL->value);

        Employee::upsert($employees, uniqueBy: ['uuid']);

        $token = session()->get(config('ehealth.api.oauth.bearer_token'));

        // Check if there are more pages to process
        if ($response->isNotLast()) {
            Bus::batch([
                new EmployeeSync(
                    legalEntity: $this->legalEntity,
                    page: 2,
                    nextEntity: null
                )
            ])
                ->withOption('legal_entity_id', $this->legalEntity->id)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->then(function (Batch $batch) use ($user) {
                    app(PermissionRegistrar::class)->forgetCachedPermissions();

                    $message = __('employees.sync.completed_successfully', [
                        'processed' => $batch->processedJobs,
                        'total' => $batch->totalJobs,
                    ]);

                    $user->notify(new EmployeeSyncCompleted($message, 'success'));
                })->catch(callback: function (Batch $batch, \Throwable $e) use ($user) {
                    $message = __('employees.sync.failed');

                    Log::error('Employee sync batch failed.', [
                        'batch_id' => $batch->id,
                        'exception' => $e
                    ]);

                    $user->notify(new EmployeeSyncCompleted($message, 'error'));
                })
                ->onQueue('sync')
                ->name('Employee Full Sync')
                ->dispatch();
        } else {
            Bus::batch($this->getEmployeeDetailsStartJob($this->legalEntity, null))
                ->withOption('legal_entity_id', $this->legalEntity->id)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->then(function (Batch $batch) use ($user) {
                    $message = __('employees.sync.completed_successfully', [
                        'processed' => $batch->processedJobs,
                        'total' => $batch->totalJobs,
                    ]);

                    $user->notify(new EmployeeSyncCompleted($message, 'success'));
                })->catch(callback: function (Batch $batch, \Throwable $e) use ($user) {
                    $message = __('employees.sync.failed');

                    Log::error('Employee sync batch failed.', [
                        'batch_id' => $batch->id,
                        'exception' => $e
                    ]);

                    $user->notify(new EmployeeSyncCompleted($message, 'error'));
                })
                ->onQueue('sync')
                ->name('Employee Full Sync')
                ->dispatch();

            // $this->batchId = $batch->id;
        }
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
     *
     * @throws JsonException
     */
    public function render(): object
    {
        $filterKey = md5($this->search . implode(',', $this->status) . json_encode($this->filter, JSON_THROW_ON_ERROR));

        return view('livewire.employee.employee-index', [
            'parties' => $this->parties,
            'dictionaries' => $this->dictionaries,
            'filterKey' => $filterKey,
        ]);
    }

    /**
     * Applies common filters for division, role, and position to a given query.
     */
    protected function applyCommonFiltersToQuery($query): void
    {
        if (!empty($this->filter['division_id'])) {
            $query->where('division_id', $this->filter['division_id']);
        }
        if (!empty($this->filter['role'])) {
            $query->where('employee_type', $this->filter['role']);
        }
        if (!empty($this->filter['position'])) {
            $query->where('position', $this->filter['position']);
        }
    }

    /**
     * Fetches unassigned EmployeeRequests and applies filters to them manually.
     *
     * @return Collection
     */
    private function getUnassignedRequests(): Collection
    {
        // Only search for drafts if "New" status is selected
        if (!in_array(Status::NEW->value, $this->status, true)) {
            return collect();
        }

        $unassignedQuery = EmployeeRequest::query()
            ->where('legal_entity_id', $this->legalEntity->id)
            ->whereIn('status', [Status::NEW->value, Status::SIGNED->value])
            ->whereNull('party_id')
            ->with(['revision', 'division']);

        // ... (the rest of the method remains the same)
        $this->applyCommonFiltersToQuery($unassignedQuery);

        // Manually apply filters that relate to party/revision data
        return $unassignedQuery->get()->filter(function (EmployeeRequest $request) {
            $revisionData = $request->revision->data ?? [];
            $partyData = $revisionData['party'] ?? [];

            if (!empty($this->search)) {
                $fullName = strtolower(($partyData['last_name'] ?? '') . ' ' . ($partyData['first_name'] ?? '') . ' ' . ($partyData['second_name'] ?? ''));
                if (!str_contains($fullName, strtolower($this->search))) {
                    return false;
                }
            }

            if (!empty($this->filter['email'])) {
                if (stripos($partyData['email'] ?? '', $this->filter['email']) === false) {
                    return false;
                }
            }

            return true;
        });
    }
}
