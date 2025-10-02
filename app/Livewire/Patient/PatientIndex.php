<?php

declare(strict_types=1);

namespace App\Livewire\Patient;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Patient\Forms\Api\PatientRequestApi;
use App\Livewire\Patient\Forms\PatientForm as Form;
use App\Models\LegalEntity;
use App\Models\Person\Person;
use App\Models\Person\PersonRequest;
use App\Rules\InDictionary;
use App\Rules\PhoneNumber;
use App\Traits\FormTrait;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Validator;

class PatientIndex extends Component
{
    use FormTrait;
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
     */
    public function searchForPerson(): void
    {
        try {
            $this->form->rulesForModelValidate('patientsFilter');
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }

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
            ->whereIn('status', ['APPLICATION', 'NEW', 'APPROVED'])
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
            try {
                $this->originalPatients = EHealth::person()->searchForPersonByParams($buildSearchRequest)->getData();
            } catch (ConnectionException $exception) {
                $this->logConnectionError($exception, 'Error when searching for person');
                Session::flash('error', "Виникла помилка. Відсутній зв'язок із ЕСОЗ");

                return;
            } catch (EHealthValidationException|EHealthResponseException $exception) {
                $this->logEHealthException($exception, 'Error when searching for person');
                Session::flash('error', 'Виникла помилка. Спробуйте пізніше.');

                return;
            }

            $this->patients = array_merge(
                $this->setPersonStatus($personRequests, 'APPLICATION'),
                $this->originalPatients = $this->setPersonStatus($this->originalPatients, 'eHEALTH'),
            );
        }
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

        Session::flash('success', 'Заявку успішно видалено.');
    }

    /**
     * Stores patient data in the DB and redirects to route by name.
     *
     * @param  string  $patientId
     * @param  string  $routeName
     * @return void
     */
    public function redirectTo(string $patientId, string $routeName): void
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

                // If validation failed, don't redirect.
                if (!$person) {
                    return;
                }
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
        // Validate incoming data
        $validator = Validator::make($patientData, [
            'uuid' => ['required', 'uuid'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'birth_country' => ['required', 'string', 'max:255'],
            'birth_settlement' => ['required', 'string', 'max:255'],
            'gender' => ['required', new InDictionary('GENDER')],
            'second_name' => ['nullable', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'size:10', Rule::unique('persons', 'tax_id')],
            'birth_certificate' => ['nullable', 'string', 'max:255'],
        ]);

        $phoneValidator = Validator::make($patientData['phones'] ?? [], [
            '*.type' => ['required', 'string', new InDictionary('PHONE_TYPE')],
            '*.number' => ['required', 'string', new PhoneNumber()]
        ]);

        if ($validator->fails() || $phoneValidator->fails()) {
            Session::flash('error', 'Некоректні дані пацієнта: ' . implode(', ', $validator->errors()->all()));

            return null;
        }

        $validated = $validator->validated();
        $validatedPhones = $phoneValidator->validated();

        try {
            $person = Person::firstOrCreate(['uuid' => $validated['uuid']], $validated);

            if (!empty($validatedPhones)) {
                $person->phones()->createMany($validatedPhones);
            }

            return $person;
        } catch (Exception $exception) {
            Session::flash('error', 'Виникла помилка, зверніться до адміністратора.');
            $this->logDatabaseErrors($exception, 'Error while creating new person');

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
        return view('livewire.patient.patient-index', [
            'paginatedPatients' => $this->paginatedPatients,
            'activeFilter' => $this->activeFilter
        ]);
    }
}
