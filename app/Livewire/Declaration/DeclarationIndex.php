<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use App\Classes\eHealth\EHealth;
use App\Enums\Declaration\Status;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\Declaration;
use App\Models\DeclarationRequest;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DeclarationIndex extends Component
{
    use WithPagination;
    use FormTrait;

    /**
     * Search by patient first and last names.
     * @var string
     */
    public string $searchByName = '';

    /**
     * Search by declaration and declaration request number
     * @var string
     */
    public string $searchByNumber = '';

    /**
     * Default types for multiselect filter
     * @var array|string[]
     */
    public array $typeFilter = ['request', 'declaration'];

    /**
     * Default status for multiselect filter
     * @var array|string[]
     */
    public array $statusFilter = ['active', 'CANCELLED'];

    /**
     * Filter for multiselect doctors
     * @var array|string[]
     */
    public array $doctorFilter = [];

    /**
     * Available doctors list
     * @var Collection
     */
    public Collection $doctors;

    /**
     * Count of active declarations.
     * @var int
     */
    public int $countActive;

    public array $employeeIds;

    public function mount(LegalEntity $legalEntity): void
    {
        $user = Auth::user();

        $this->employeeIds = $user?->employees()->pluck('id')->all();

        if ($user?->hasRole('OWNER')) {
            $this->doctors = $this->getDoctors();
        } else {
            $this->countActive = Declaration::whereIn('employee_id', $this->employeeIds)->count();
        }
    }

    #[Computed]
    public function declarations(): LengthAwarePaginator
    {
        $user = Auth::user();

        $declarations = collect();
        $declarationRequests = collect();

        if ($user?->can('viewAny', Declaration::class)) {
            $declarations = Declaration::where('legal_entity_id', legalEntity()->id)
                ->select(['id', 'person_id', 'employee_id', 'legal_entity_id', 'declaration_number', 'status'])
                ->when(
                    !$user?->hasRole('OWNER'),
                    fn (Builder $query) => $query->whereIn('employee_id', $this->employeeIds)
                )->get()
                ->each->setAttribute('type', 'declaration');
        }

        // Don't show declaration requests for OWNER
        if (!$user?->hasRole('OWNER') && $user?->can('viewAny', DeclarationRequest::class)) {
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

    public function approve(int $patientId, int $declarationRequestId): void
    {
        if (!$this->ensureAbility('approve', 'У вас немає дозволу на підтвердження заявки на подання декларації')) {
            return;
        }

        $this->redirectRoute(
            'declaration.edit',
            [legalEntity(), 'patientId' => $patientId, 'declarationRequestId' => $declarationRequestId],
            navigate: true
        );
    }

    public function sign(int $patientId, int $declarationRequestId): void
    {
        if (!$this->ensureAbility('sign', 'У вас немає дозволу на підписання заявки на подання декларації')) {
            return;
        }

        Session::flash('showSignModal');

        $this->redirectRoute(
            'declaration.edit',
            [legalEntity(), 'patientId' => $patientId, 'declarationRequestId' => $declarationRequestId],
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
            Session::flash('error', 'Виникла помилка. Зверніться до адміністратора.');

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
     * @param  int  $id
     * @return void
     */
    public function destroy(int $id): void
    {
        $declarationRequest = DeclarationRequest::select(['id', 'legal_entity_id', 'employee_id', 'status'])
            ->findOrFail($id);

        if (Auth::user()?->cannot('destroy', $declarationRequest)) {
            Session::flash('error', 'У вас немає дозволу на видалення заявки на подання декларації');

            return;
        }

        try {
            DeclarationRequest::destroy($id);
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
        if (Auth::user()?->cannot($ability, DeclarationRequest::class)) {
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
        return Employee::where('employee_type', 'DOCTOR')
            ->with('party:id,last_name,first_name')
            ->where('legal_entity_id', legalEntity()->id)
            ->whereHas('declarations')
            ->select(['id', 'uuid', 'user_id', 'party_id'])
            ->get()
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
