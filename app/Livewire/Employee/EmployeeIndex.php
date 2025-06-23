<?php

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\Api\EmployeeApi;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\BaseEmployee;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Repositories\EmployeeRepository;
use App\Traits\FormTrait;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;

#[AllowDynamicProperties]
class EmployeeIndex extends Component
{
    use FormTrait;

    public Collection $parties;

    // This property is kept for backwards compatibility with the Blade template's @php block.
    public Collection $employees;

    // Properties for filtering.
    public string $status = 'APPROVED';
    public string $search = '';

    // Properties for the dismissal modal, likely managed by FormTrait.
    public string $dismiss_text = '';
    public int $dismissed_id = 0;

    private LegalEntity $legalEntity;

    public array $dictionaryNames = [
        'POSITION', 'EMPLOYEE_TYPE', 'GENDER'
    ];

    /**
     * The boot method is called on every request.
     * It's the perfect place to initialize properties that are always needed.
     */
    public function boot(): void
    {
        $this->legalEntity = legalEntity();
    }

    /**
     * The mount method is called only on the initial page load.
     * We use it to set up the initial state.
     */
    public function mount(LegalEntity $legalEntity): void
    {
        $this->getDictionary();
        $this->parties = new Collection();
        $this->employees = new Collection();
        $this->loadParties();
    }

    /**
     * Re-fetch data when a filter changes.
     */
    public function updated($property): void
    {
        if (in_array($property, ['status', 'search'])) {
            $this->loadParties();
        }
    }

    /**
     * The main method to fetch and structure data for the view.
     */
    public function loadParties(): void
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

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('last_name', 'ilike', "%{$this->search}%")
                    ->orWhere('first_name', 'ilike', "%{$this->search}%")
                    ->orWhere('second_name', 'ilike', "%{$this->search}%");
            });
        }

        if ($this->status === 'APPROVED') {
            $query->whereHas('employees', fn($q) => $q->where('status', 'APPROVED'));
        } elseif ($this->status === 'NEW') {
            $query->whereHas('employeeRequests', fn($q) => $q->where('status', 'NEW'));
        }

        $this->parties = $query->get();
        $this->employees = $this->parties->flatMap(fn($party) => $party->employees->concat($party->employeeRequests));
    }

    /**
     * Prepares and shows the dismissal modal.
     */
    public function showModalDismissed(int $id): void
    {
        $employee = Employee::find($id);
        if (!$employee) return;

        $this->dismiss_text = ($employee->employee_type === 'DOCTOR')
            ? __('forms.dismissed_text_doctor')
            : __('forms.dismissed_text');

        $this->dismissed_id = $employee->id;
        $this->openModal(); // Method from FormTrait
    }

    #[On('refreshPage')]
    public function refreshPage()
    {
        $this->dispatch('$refresh');
    }

    public function getLastStoreId()
    {
        if (Cache::has($this->employeeCacheKey) && !empty(Cache::get($this->employeeCacheKey)) && is_array(Cache::get($this->employeeCacheKey))) {
            $this->storeId = array_key_last(Cache::get($this->employeeCacheKey));
        }
        $this->storeId++;
    }

    public function getEmployeesCache(): \Illuminate\Support\Collection
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

        // Your API call logic for dismissal
        // $dismissed = EmployeeRequestApi::dismissedEmployeeRequest($employee->uuid);

        // if (!empty($dismissed)) {
        //     $employee->update([
        //         'status'   => 'DISMISSED',
        //         'end_date' => \Carbon\Carbon::now()->format('Y-m-d'),
        //     ]);
        // }

        $this->closeModal(); // This method comes from your FormTrait
        $this->loadParties(); // FIX: Call the correct data loading method to refresh the list
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
        $this->loadParties();
        // $this->dispatch('refreshPage');
    }

    private function dispatchErrorMessage(string $message, string $type = 'success', array $errors = []): void
    {
        $this->dispatch('flashMessage', [
            'message' => $message,
            'type'    => $type,
            'errors'  => $errors
        ]);
    }

    public function render()
    {
        // $perPage = config('pagination.per_page');
        // $employees = Auth::user()->legalEntity->employees()->paginate($perPage);

        // return view('livewire.employee.employee-index', compact('employees'));
        return view('livewire.employee.employee-index');
    }
}
