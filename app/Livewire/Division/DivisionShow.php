<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use App\Models\Division;
use App\Models\LegalEntity;
use App\Traits\AddressSearch;
use App\Traits\WorkTimeUtilities;

class DivisionShow extends DivisionComponent
{
    use WorkTimeUtilities,
        AddressSearch;

    public function mount(LegalEntity $legalEntity, Division $division): void
    {
        if (!$division) {
            abort(404);
        }

        $this->setDivisionData($division);

        $this->divisionForm->initWorkingHours($this->weekdays);

        $this->setDictionary();
    }

    /**
     * Set the division form data based on the provided Division model.
     *
     * - Sets the main division parameters from the model.
     * - Assigns the address and phones to the form.
     * - Initializes working hours if not already set.
     *
     * @param Division $division
     *
     * @return void
     */
    public function setDivisionData(Division $division)
    {
        $this->divisionForm->setDivision($division->toArray());

        $this->divisionForm->setDivisionParam('addresses', $division->address->toArray());

        $this->address = $this->divisionForm->getDivisionParam('addresses');

        $this->divisionForm->setDivisionParam('phones', $division->phones->toArray()[0]); // TODO: need refactor this to multiphone array

        $this->divisionForm->setDivisionParam('id', $division->id ?? '');
        $this->divisionForm->setDivisionParam('uuid', $division->uuid ?? '');

        if ($this->divisionForm->isDivisionParamExistAndNull('working_hours')) {
            $this->divisionForm->initWorkingHours($this->weekdays);
        }
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render()
    {
        return view('livewire.division.division-show');
    }
}
