<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use Livewire\Component;
use App\Models\Division;
use App\Models\LegalEntity;
use App\Traits\AddressSearch;
use App\Repositories\Repository;
use App\Traits\WorkTimeUtilities;
use Illuminate\Http\RedirectResponse;
use App\Livewire\Division\Forms\DivisionForm as DivisionFormRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportRedirects\Redirector;

// TODO: divide this class onto three ones: Divisions as parent class and Division Create & DivisionUpdate extends Division
class DivisionForm extends Component
{
    use WorkTimeUtilities,
        AddressSearch;

    public DivisionFormRequest $formService;

    public string $mode = 'create';

    public array $dictionaries;

    protected array $divisionAllowedPhoneTypeKeys = ['MOBILE','LAND_LINE'];
    protected array $divisionAllowedTypeKeys = ['CLINIC', 'AMBULANT_CLINIC', 'FAP'];

    public function mount(LegalEntity $legalEntity, $id = null)
    {
        if (!empty($id)) {
            $division = Division::findOrFail($id);

            if (Auth::user()->cannot('update', $division)) {
                abort(403);
            }

            $this->getDivision($division);
            $this->mode = 'edit';
        }

        $this->formService->initWorkingHours($this->weekdays);

        $this->dictionaries = [
            'SETTLEMENT_TYPE' => dictionary()->getDictionary('SETTLEMENT_TYPE'),
            'PHONE_TYPE' => dictionary()->getDictionary('PHONE_TYPE', false)
                ->allowedKeys($this->divisionAllowedPhoneTypeKeys)
                ->toArrayRecursive(),
            'DIVISION_TYPE' => dictionary()->getDictionary('DIVISION_TYPE', false)
                ->allowedKeys($this->divisionAllowedTypeKeys)
                ->toArrayRecursive()
        ];
    }

    public function getDivision(Division $division)
    {
        $this->formService->setDivision($division->toArray());

        $this->formService->setDivisionParam('addresses', $division->address->toArray());

        $this->address = $this->formService->getDivisionParam('addresses');

        $this->formService->setDivisionParam('phones', $division->phones->toArray()[0]); // TODO: need refactor this to multiphone array

        if ($this->formService->isDivisionParamExistAndNull('working_hours')) {
            $this->formService->initWorkingHours($this->weekdays);
        }
    }

    // Validate the data comning from the form(s)
    public function validateDivision(): bool
    {
        $error = $this->formService->doValidation();

        if ($error) {
            session()->flash('error', $error);

            return false;
        } else {
            return true;
        }
    }

    // Create new Division (depends on $this->mode value = 'create')
    public function store()
    {
        if (Auth::user()->cannot('create', Division::class)) {
            session()->flash('error', __('У вас немає дозволу на створення місця надання послуг'));

            return;
        }

        if ($this->validateDivision()) {
            $this->updateOrCreate();
        }
    }

    // Update/Moify the existent Division (depends on $this->mode value = 'edit')
    public function update(): void
    {
        if (Auth::user()->cannot('update', Division::find($this->formService->division['id']))) {
            session()->flash('error', __('У вас немає дозволу на редагування цього місця надання послуг'));

            return;
        }

        if ($this->validateDivision()) {
            $this->updateOrCreate();
        }
    }

    /**
     * Combined method used both creation and modification Division's data
     *
     * @return Redirector|RedirectResponse|null
     */
    public function updateOrCreate(): Redirector|RedirectResponse|null
    {
        $response = $this->mode === 'edit'
            ? $this->formService->updateDivision()
            : $this->formService->createDivision();

        if ($response) {
            Repository::division()->saveDivisionResponseData($response, legalEntity());

            return redirect()->route('division.index', [legalEntity()])->with('success', __('Запит виконано успішно'));
        }

        session()->flash('error', __('Помилка в процесі обробки запиту'));

        return null;
    }

    /**
     * Proxy method!
     * Proceed data when day is off and hasn't the schedule at all
     *
     * @param  mixed  $day
     * @param  mixed  $allDayWork
     *
     * @return void
     */
    public function notWorking($day, $allDayWork)
    {
        $this->formService->notWorking($day, $allDayWork);
    }

    /**
     * Proxy method!
     * Add shift(s) to the current day's schedule
     *
     * @param  string  $day
     *
     * @return void
     */
    public function addAvailableShift(string $day): void
    {
        $this->formService->addAvailableShift($day);
    }

    /**
     * Proxy method!
     * Remove the selected shift from the day's schedule
     *
     * @param  string  $day  key value aka 'mon', 'tue' etc.
     * @param  int  $shift  shift's numeric position in array
     *
     * @return void
     */
    public function deleteShift(string $day, int $shift)
    {
        $this->formService->deleteShift($day, $shift);
    }

    /**
     * Proxy method!
     * Called when no shift should be present in the day's schedule.
     * But one time range must left anyway!
     *
     * @param  mixed  $day
     * @param  mixed  $isShift  true if shift schedule is activated
     * @return void
     */
    public function noShift($day, $isShift)
    {
        $this->formService->noShift($day, $isShift);
    }

    /**
     * Render with pagination
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        $currentDivision = [];
        $division = $this->formService->getDivision();

        if (!empty($division)) {
            $currentDivision['name'] = !empty($division['name'])
                ? $division['name']
                : '';
            $currentDivision['type'] = !empty($division['type'])
                ? dictionary()->getDictionary('DIVISION_TYPE', false)->getValue($division['type'])
                : '';
        }

        return view('livewire.division.division-form-create', compact('currentDivision'));
    }
}
