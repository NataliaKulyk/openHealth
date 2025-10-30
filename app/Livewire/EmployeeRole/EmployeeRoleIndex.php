<?php

declare(strict_types=1);

namespace App\Livewire\EmployeeRole;

use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Models\EmployeeRole;
use App\Models\LegalEntity;
use App\Repositories\Repository;
use App\Traits\FormTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
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
    public array $statusFilter = ['ACTIVE', 'INACTIVE'];

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

    #[Computed]
    public function employeeRoles(): LengthAwarePaginator
    {
        return EmployeeRole::with([
            'employee:id,party_id',
            'employee.party:id,first_name,last_name,second_name',
            'healthcareService:id,legal_entity_id,division_id,speciality_type,providing_condition',
            'healthcareService.legalEntity:id',
            'healthcareService.division:id,name'
        ])
            ->select(['id', 'uuid', 'employee_id', 'healthcare_service_id', 'status', 'is_active'])
            ->whereHas('healthcareService', function (Builder $query) {
                $query->select('id')->where('legal_entity_id', legalEntity()->id);
            })
            ->when($this->employeeSearch, function (Builder $query) {
                $query->whereHas('employee', function (Builder $employeeQuery) {
                    $employeeQuery->select('id', 'party_id')
                        ->whereHas('party', function (Builder $partyQuery) {
                            $partyQuery->select('id')
                                ->where('first_name', 'ILIKE', "%$this->employeeSearch%")
                                ->orWhere('last_name', 'ILIKE', "%$this->employeeSearch%")
                                ->orWhere('second_name', 'ILIKE', "%$this->employeeSearch%");
                        });
                });
            })
            ->when($this->specialityTypeFilter, function (Builder $query) {
                $query->whereHas('healthcareService', function (Builder $subQuery) {
                    $subQuery->select('speciality_type')
                        ->where('speciality_type', $this->specialityTypeFilter);
                });
            })
            ->when($this->statusFilter, function (Builder $query) {
                $query->whereIn('status', $this->statusFilter);
            })
            ->orderByDesc('created_at')
            ->paginate(config('pagination.per_page'));
    }

    public function render(): View
    {
        return view('livewire.employee-role.employee-role-index', ['employeeRoles' => $this->employeeRoles]);
    }
}
