<?php

declare(strict_types=1);

namespace App\Livewire\Patient;

use App\Classes\eHealth\Api\PersonApi;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Core\Arr;
use App\Livewire\Patient\Forms\Api\PatientRequestApi;
use App\Livewire\Patient\Forms\PatientForm as Form;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class PatientIndex extends Component
{
    use WithPagination;

    /**
     * List of founded person.
     * @var array
     */
    public array $patients = [];

    /**
     * Patient data from eHealth response.
     * @var array
     */
    public array $originalPatients = [];

    public Form $form;

    /**
     * Active filter for patients.
     *
     * @var string
     */
    public string $activeFilter = 'all';

    public function mount(LegalEntity $legalEntity): void
    {
    }

    /**
     * Reset pagination when filters are updated.
     *
     * @param  string  $property
     * @return void
     */
    public function updated(string $property): void
    {
        if ($property === 'activeFilter') {
            $this->resetPage();
        }
    }

    /**
     * Reset all filters to default values.
     *
     * @return void
     */
    public function resetFilters(): void
    {
        $this->activeFilter = 'all';
        $this->resetPage();
    }

    /**
     * Get paginated patients with filtering.
     *
     * @return LengthAwarePaginator
     */
    #[Computed]
    public function paginatedPatients(): LengthAwarePaginator
    {
        $collection = collect($this->patients);

        // Filter by active filter
        if ($this->activeFilter !== 'all') {
            $collection = $collection->filter(function (array $patient) {
                return $patient['status'] === $this->activeFilter;
            });
        }

        return new LengthAwarePaginator(
            $collection->forPage($this->getPage(), 10),
            $collection->count(),
            10,
            $this->getPage()
        );
    }

    /**
     * Search for person with provided filters.
     *
     * @return void
     * @throws ApiException|ValidationException
     */
    public function searchForPerson(): void
    {
        $this->form->rulesForModelValidate('patientsFilter');

        // Prepare filters for local DB search
        $filtersSnake = Arr::toSnakeCase($this->form->patientsFilter);
        $phoneNumber = $filtersSnake['phoneNumber'] ?? null;
        unset($filtersSnake['phone_number']);

        // Search in our DB
        $this->originalPatients = Person::with('phones')
            ->select([
                'id', 'uuid', 'first_name', 'last_name', 'second_name', 'birth_date', 'tax_id', 'verification_status'
            ])
            ->where($filtersSnake)
            ->when($phoneNumber, static function ($query) use ($phoneNumber) {
                $query->whereHas('phones', static function (Builder $query) use ($phoneNumber) {
                    $query->where('number', $phoneNumber);
                });
            })
            ->get()
            ->toArray();

        // Search for applications (person_requests)
        $personRequests = PersonRequest::with('phones')
            ->select(['id', 'status', 'first_name', 'last_name', 'second_name', 'birth_date', 'tax_id'])
            ->where($filtersSnake)
            ->where('status', 'APPLICATION')
            ->when($phoneNumber, static function ($query) use ($phoneNumber) {
                $query->whereHas('phones', static function (Builder $query) use ($phoneNumber) {
                    $query->where('number', $phoneNumber);
                });
            })
            ->get()
            ->toArray();

        // If found in our DB, show that result
        if (!empty($this->originalPatients)) {
            $this->patients = array_merge(
                $this->setPersonStatus($personRequests, 'APPLICATION'),
                $this->originalPatients = array_map(static function (array $patient) {
                    return array_merge($patient, ['status' => $patient['verification_status']]);
                }, $this->originalPatients)
            );
        } else {
            // Otherwise search in eHealth
            $buildSearchRequest = PatientRequestApi::buildSearchForPerson($this->form->patientsFilter);
            $this->originalPatients = PersonApi::searchForPersonByParams($buildSearchRequest);

            $this->patients = array_merge(
                $this->setPersonStatus($personRequests, 'APPLICATION'),
                $this->originalPatients = $this->setPersonStatus($this->originalPatients, 'eHEALTH'),
            );
        }
    }

    /**
     * Redirect to patient data route.
     *
     * @param  string  $patientId
     * @return void
     */
    public function redirectToRecord(string $patientId): void
    {
        $this->handleRedirect($patientId, 'patient.patient-data');
    }

    /**
     * Redirect to create encounter route.
     *
     * @param  string  $patientId
     * @return void
     */

    public function redirectToEncounter(string $patientId): void
    {
        $this->handleRedirect($patientId, 'encounter.create');
    }

    /**
     * Delete person request.
     *
     * @param  int  $id
     * @return void
     */
    public function removeApplication(int $id): void
    {
        PersonRequest::destroy($id);
        $this->dispatch('flashMessage', [
            'message' => 'Заявку успішно видалено.',
            'type' => 'success'
        ]);
    }

    /**
     * Redirect to the diagnostic report creation page.
     *
     * @param  int  $patientId
     * @return void
     */
    public function createDiagnosticReport(int $patientId): void
    {
        $this->redirectRoute('diagnostic-report.create', [legalEntity(), 'patientId' => $patientId]);
    }

    /**
     * Stores patient data in the DB and redirects to route by name.
     *
     * @param  string  $patientId
     * @param  string  $routeName
     * @return void
     */
    private function handleRedirect(string $patientId, string $routeName): void
    {
        if (uuid_is_valid($patientId)) {
            // IF UUID is valid, then find for it in DB
            $patientData = collect($this->getOriginalPatients())->firstWhere('id', $patientId);
            $person = Person::firstWhere('uuid', $patientId);

            // Crete person in DB if not exist.
            if (!$person) {
                $patientData['uuid'] = $patientData['id'];
                unset($patientData['id'], $patientData['status']);

                $person = $this->storeNewPerson($patientData);
            }

            $this->redirectRoute($routeName, [legalEntity(), 'patientId' => $person->id]);
        } else {
            $this->redirectRoute($routeName, [legalEntity(), 'patientId' => $patientId]);
        }
    }

    /**
     * Get the original patient's data.
     *
     * @return array
     */
    private function getOriginalPatients(): array
    {
        return $this->originalPatients;
    }

    /**
     * Store new person from eHealth in DB.
     *
     * @param  array  $patientData
     * @return Person|null
     */
    private function storeNewPerson(array $patientData): ?Person
    {
        try {
            $person = Person::firstOrCreate(['uuid' => $patientData['uuid']], $patientData);

            if (!empty($patientData['phones'])) {
                foreach ($patientData['phones'] as $phoneData) {
                    $person->phones()->firstOrCreate($phoneData);
                }
            }

            return $person;
        } catch (Exception $exception) {
            $this->dispatch('flashMessage', [
                'message' => 'Виникла помилка, зверніться до адміністратора.',
                'type' => 'error'
            ]);
            Log::channel('db_errors')->error('Error while creating new person', [
                'error' => $exception->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Add status to patients.
     *
     * @param  array  $persons
     * @param  string  $status
     * @return array
     */
    private function setPersonStatus(array $persons, string $status): array
    {
        return array_map(static function ($patient) use ($status) {
            $patient['status'] = $status;

            return $patient;
        }, $persons);
    }

    public function render(): View
    {
        return view('livewire.patient.index', [
            'paginatedPatients' => $this->paginatedPatients,
            'activeFilter' => $this->activeFilter
        ]);
    }
}
