<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use App\Classes\eHealth\EHealth;
use App\Enums\Declaration\Status;
use App\Models\Declaration;
use App\Models\DeclarationRequest;
use App\Models\LegalEntity;
use App\Traits\FormTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class DeclarationIndex extends Component
{
    use WithPagination;
    use FormTrait;

    public string $search = '';

    /**
     * Default types for multiselect filter
     * @var array|string[]
     */
    public array $typeFilter = ['request', 'declaration'];

    public function mount(LegalEntity $legalEntity): void
    {
    }

    #[Computed]
    public function declarations(): LengthAwarePaginator
    {
        $user = Auth::user();
        $declarationRequests = DeclarationRequest::with(['person', 'employee'])
            ->where('legal_entity_id', legalEntity()->id)
            ->when(
                !$user?->hasRole('OWNER'),
                fn (Builder $query) => $query->whereIn('employee_id', $user->employees()->pluck('id'))
            )
            ->whereNotIn('status', [Status::SIGNED->value])
            ->get()
            ->each->setAttribute('type', 'request');

        $declarations = Declaration::with(['person', 'employee'])
            ->where('legal_entity_id', legalEntity()->id)
            ->when(
                !$user?->hasRole('OWNER'),
                fn (Builder $query) => $query->whereIn('employee_id', $user->employees()->pluck('id'))
            )
            ->get()
            ->each->setAttribute('type', 'declaration');

        $allItems = $declarationRequests->concat($declarations);

        // Filter by type
        if (!empty($this->typeFilter)) {
            $allItems = $allItems->filter(
                fn (DeclarationRequest|Declaration $item) => in_array($item->type, $this->typeFilter, true)
            );
        }

        // Search by first and last name
        if (!empty($this->search)) {
            $searchTerm = Str::lower(trim($this->search));

            $allItems = $allItems->filter(function (DeclarationRequest|Declaration $item) use ($searchTerm) {
                $last = Str::lower(data_get($item, 'person.last_name', ''));
                $first = Str::lower(data_get($item, 'person.first_name', ''));

                return Str::contains($last, $searchTerm) || Str::contains($first, $searchTerm);
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

        session()?->flash('showSignModal');

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

            if (!$response->successful()) {
                $this->logEHealthError($response, 'Error while rejecting declaration request');
                $this->flashGeneralError();

                return;
            }
        } catch (ConnectionException $exception) {
            $this->logConnectionError($exception, 'Error while rejecting declaration request');
            $this->flashGeneralError();

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
            $this->dispatch('flashMessage', [
                'message' => $errorMessage,
                'type' => 'error'
            ]);

            return false;
        }

        return true;
    }

    public function render(): View
    {
        return view('livewire.declaration.declaration-index', [
            'declarations' => $this->declarations
        ]);
    }
}
