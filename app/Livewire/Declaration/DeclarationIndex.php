<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use Throwable;
use Exception;
use Livewire\Component;
use Illuminate\View\View;
use Illuminate\Bus\Batch;
use App\Traits\FormTrait;
use App\Models\Declaration;
use App\Models\LegalEntity;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Jobs\DeclarationsSync;
use App\Repositories\Repository;
use App\Classes\eHealth\EHealth;
use App\Enums\Declaration\Status;
use App\Models\Employee\Employee;
use Livewire\Attributes\Computed;
use App\Models\DeclarationRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;
use App\Notifications\SyncNotification;
use App\Traits\BatchLegalEntityQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Client\ConnectionException;
use App\Notifications\DeclarationSyncCompleted;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;

class DeclarationIndex extends Component
{
    use BatchLegalEntityQueries,
        WithPagination,
        FormTrait;

    /**
     * Search by patient first and last names.
     *
     * @var string
     */
    public string $searchByName = '';

    /**
     * Search by declaration and declaration request number
     *
     * @var string
     */
    public string $searchByNumber = '';

    /**
     * Default types for multiselect filter
     *
     * @var array|string[]
     */
    public array $typeFilter = ['request', 'declaration'];

    /**
     * Default status for multiselect filter
     *
     * @var array|string[]
     */
    public array $statusFilter = ['active', 'CANCELLED'];

    /**
     * Filter for multiselect doctors
     *
     * @var array|string[]
     */
    public array $doctorFilter = [];

    /**
     * Available doctors list
     *
     * @var Collection
     */
    public Collection $doctors;

    /**
     * Count of active declarations.
     *
     * @var int
     */
    public int $countActive;

    public array $employeeIds;

    public bool $isFiltersApplied = false;

    public function mount(LegalEntity $legalEntity): void
    {
        $user = Auth::user();

        $this->employeeIds = $user->employees()->filterByLegalEntityId($legalEntity->id)->pluck('id')->all();

        if ($user->hasRole('OWNER')) {
            $this->doctors = $this->getDoctors();
        } else {
            $this->countActive = Declaration::whereIn('employee_id', $this->employeeIds)->count();
        }
    }

    public function search(): void
    {
        $this->resetPage();
        $this->isFiltersApplied = true;
    }

    public function resetFilters(): void
    {
        $this->searchByName = '';
        $this->searchByNumber = '';
        $this->typeFilter = ['request', 'declaration'];
        $this->statusFilter = ['active', 'CANCELLED'];
        $this->doctorFilter = [];

        $this->resetPage();
    }

    #[Computed]
    public function declarations(): LengthAwarePaginator
    {
        $user = Auth::user();

        $declarations = collect();
        $declarationRequests = collect();

        if ($user->can('viewAny', Declaration::class)) {
            $declarations = Declaration::where('legal_entity_id', legalEntity()->id)
                ->select(['id', 'person_id', 'employee_id', 'legal_entity_id', 'declaration_number', 'status'])
                ->when(
                    !$user->hasRole('OWNER'),
                    fn (Builder $query) => $query->whereIn('employee_id', $this->employeeIds)
                )->with([
                    'person:id,first_name,last_name,second_name,birth_date',
                    'employee:id,uuid,party_id',
                    'employee.party:id,first_name,last_name,second_name'
                ])
                ->get()
                ->each->setAttribute('type', 'declaration');
        }

        // Don't show declaration requests for OWNER
        if (!$user->hasRole('OWNER') && $user->can('viewAny', DeclarationRequest::class)) {
            $declarationRequests = DeclarationRequest::where('legal_entity_id', legalEntity()->id)
                ->select(['id', 'uuid', 'person_id', 'employee_id', 'declaration_number', 'status'])
                ->whereNotIn('status', [Status::SIGNED->value])
                ->with([
                    'person:id,first_name,last_name,second_name,birth_date',
                    'employee:id,party_id',
                    'employee.party:id,first_name,last_name,second_name'
                ])
                ->get()
                ->each->setAttribute('type', 'request');
        }

        $allItems = $declarationRequests->concat($declarations);

        if ($this->isFiltersApplied) {
            // Filter by type
            if (!empty($this->typeFilter)) {
                $allItems = $allItems->filter(
                    fn (DeclarationRequest|Declaration $item) => in_array($item->type, $this->typeFilter, true)
                );
            }

            // Filter by status
            if (!empty($this->statusFilter)) {
                $allItems = $allItems->filter(function (DeclarationRequest|Declaration $item) {
                    if ($item instanceof Declaration) {
                        return in_array($item->status->value, $this->statusFilter, true);
                    }

                    return true;
                });
            }

            // Search by first and last name
            if (!empty($this->searchByName)) {
                $searchTerm = Str::lower(trim($this->searchByName));

                $allItems = $allItems->filter(function (DeclarationRequest|Declaration $item) use ($searchTerm) {
                    $last = Str::lower(data_get($item, 'person.last_name', ''));
                    $first = Str::lower(data_get($item, 'person.first_name', ''));

                    return Str::contains($last, $searchTerm) || Str::contains($first, $searchTerm);
                });
            }

            // Search by declaration number
            if (!empty($this->searchByNumber)) {
                $searchTerm = Str::lower(trim($this->searchByNumber));

                $allItems = $allItems->filter(function (DeclarationRequest|Declaration $item) use ($searchTerm) {
                    $number = Str::lower($item->declaration_number ?? '');

                    return Str::contains($number, $searchTerm);
                });
            }

            // Filter by doctors
            if (!empty($this->doctorFilter)) {
                $allItems = $allItems->filter(function (DeclarationRequest|Declaration $item) {
                    if ($item instanceof Declaration) {
                        return in_array($item->employee->uuid, $this->doctorFilter, true);
                    }

                    return false;
                });
            }
        }

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


    public function sync(): void
    {
        if (Auth::user()->cannot('sync', Declaration::class)) {
            session()->flash('error', __('У вас немає дозволу на синхронізацію декларацій'));

            return;
        }

        $legalEntity = legalEntity();

        $user = Auth::user();
        $user->notify(new SyncNotification('declaration', 'started'));

        // Get declarations from eHealth filtered by legal entity
        $query = ['legal_entity_id' => $legalEntity->uuid];

        // If user is doctor, get only his declarations
        if ($user->hasRole('DOCTOR') && !$user->hasRole('OWNER')) {
            $query['employee_id'] = Auth::user()
                ->employees()
                ->where('party_id', Auth::user()->party->id)
                ->first()->uuid;
        }

        try {
            $response = EHealth::declaration()->getMany(query: $query, groupByEntities: true);

            $declarations = $response->validate();

            Repository::declaration()->storeMany($declarations);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while syncing declaration requests');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error while syncing declaration requests');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        } catch (Exception $exception) {
            $this->logDatabaseErrors($exception, 'Error while syncing declaration requests');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }

        $token = session()->get(config('ehealth.api.oauth.bearer_token'));

        // Check if there are more pages to process
        if ($response->isNotLast()) {
            Bus::batch([
                new DeclarationsSync(
                    legalEntity: $legalEntity,
                    page: 2,
                    nextEntity: null
                )
            ])
                ->withOption('legal_entity_id', $legalEntity->id)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->then(function (Batch $batch) use ($user) {
                    $message = __('declarations.sync.completed', [
                        'processed' => $batch->processedJobs,
                        'total' => $batch->totalJobs,
                    ]);

                    $user->notify(new DeclarationSyncCompleted($message, 'success'));
                })->catch(callback: function (Batch $batch, Throwable $err) use ($user) {
                    $message = __('declarations.sync.failed');

                    Log::error('Declaration sync batch failed.', [
                        'batch_id' => $batch->id,
                        'exception' => $err
                    ]);

                    $user->notify(new DeclarationSyncCompleted($message, 'error'));
                })
                ->onQueue('sync')
                ->name('Declarations Full Sync')
                ->dispatch();
        } else {
            Bus::batch($this->getDeclarationRequestsStartJob($legalEntity, null))
                ->withOption('legal_entity_id', $legalEntity->id)
                ->withOption('token', Crypt::encryptString($token))
                ->withOption('user', $user)
                ->then(function (Batch $batch) use ($user) {
                    $message = __('declarationRequests.sync.completed', [
                        'processed' => $batch->processedJobs,
                        'total' => $batch->totalJobs,
                    ]);

                    $user->notify(new DeclarationSyncCompleted($message, 'success'));
                })->catch(callback: function (Batch $batch, Throwable $err) use ($user) {
                    $message = __('declarationRequests.sync.failed');

                    Log::error('DeclarationRequest sync batch failed.', [
                        'batch_id' => $batch->id,
                        'exception' => $err
                    ]);

                    $user->notify(new DeclarationSyncCompleted($message, 'error'));
                })
                ->onQueue('sync')
                ->name('DeclarationRequest Full Sync')
                ->dispatch();
        }

        session()->flash('success', __('declarations.sync.started'));
    }

    public function approve(int $patientId, int $declarationRequestId): void
    {
        if (!$this->ensureAbility('approve', 'У вас немає дозволу на підтвердження заявки на подання декларації')) {
            return;
        }

        $declarationRequest = DeclarationRequest::findOrFail($declarationRequestId);

        $this->redirectRoute(
            'declaration.edit',
            [legalEntity(), 'patientId' => $patientId, 'declarationRequest' => $declarationRequest],
            navigate: true
        );
    }

    public function sign(int $patientId, int $declarationRequestId): void
    {
        if (!$this->ensureAbility('sign', 'У вас немає дозволу на підписання заявки на подання декларації')) {
            return;
        }

        Session::flash('showSignModal');
        $declarationRequest = DeclarationRequest::findOrFail($declarationRequestId);

        $this->redirectRoute(
            'declaration.edit',
            [legalEntity(), 'patientId' => $patientId, 'declarationRequest' => $declarationRequest],
            navigate: true
        );
    }

    public function reject(string $declarationUuid): void
    {
        if (!$this->ensureAbility('reject', 'У вас немає дозволу на відхилення заявки на подання декларації')) {
            return;
        }

        try {
            $response = EHealth::declarationRequest()->reject($declarationUuid);

            ['status' => $status, 'statusReason' => $statusReason] = $response->getData();

            Repository::declarationRequest()->updateStatuses($declarationUuid, $status, $statusReason);
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while rejecting declaration request');
            Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

            return;
        } catch (EHealthValidationException|EHealthResponseException $exception) {
            $this->logEHealthException($exception, 'Error while rejecting declaration request');

            if ($exception instanceof EHealthValidationException) {
                Session::flash('error', $exception->getFormattedMessage());
            } else {
                Session::flash('error', 'Помилка від ЕСОЗ: ' . $exception->getMessage());
            }

            return;
        } catch (Exception $exception) {
            $this->logDatabaseErrors($exception, 'Error updating status in declaration request');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    /**
     * Delete declaration request with status DRAFT from DB.
     *
     * @param  DeclarationRequest  $declarationRequest
     * @return void
     */
    public function delete(DeclarationRequest $declarationRequest): void
    {
        if (Auth::user()->cannot('delete', $declarationRequest)) {
            Session::flash('error', 'У вас немає дозволу на видалення заявки на подання декларації');

            return;
        }

        try {
            DeclarationRequest::destroy($declarationRequest->id);
        } catch (Exception $exception) {
            $this->logDatabaseErrors($exception, 'Error while deleting declaration request');
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

            return;
        }
    }

    /**
     * Ensure that the authenticated user has the given ability; if not, flash an error message.
     *
     * @param  string  $ability
     * @param  string  $errorMessage
     * @return bool
     */
    protected function ensureAbility(string $ability, string $errorMessage): bool
    {
        if (Auth::user()->cannot($ability, DeclarationRequest::class)) {
            Session::flash('error', $errorMessage);

            return false;
        }

        return true;
    }

    /**
     * Get list of doctors in current legal entity.
     *
     * @return Collection
     */
    protected function getDoctors(): Collection
    {
        return Employee::with('party:id,last_name,first_name')
            ->doctor()
            ->filterByLegalEntityId(legalEntity()->id)
            ->whereHas('declarations')
            ->get(['id', 'uuid', 'user_id', 'party_id'])
            ->map(fn (Employee $doctor) => [
                'uuid' => $doctor->uuid,
                'full_name' => trim($doctor->party->fullName)
            ]);
    }

    public function render(): View
    {
        return view('livewire.declaration.declaration-index', [
            'declarations' => $this->declarations
        ]);
    }
}
