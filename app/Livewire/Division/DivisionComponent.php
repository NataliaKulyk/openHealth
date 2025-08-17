<?php

declare(strict_types=1);

namespace App\Livewire\Division;

use Livewire\Component;
use App\Traits\FormTrait;
use App\Livewire\Division\Forms\DivisionForm;

class DivisionComponent extends Component
{
    use FormTrait;

    /**
     * The form model instance for handling division data.
     *
     * @var DivisionForm
     */
    public DivisionForm $divisionForm;

    /**
     * An array containing working hours configuration.
     *
     * @var array|null
     */
    public ?array $working_hours = [
        'mon' => 'Понеділок',
        'tue' => 'Вівторок',
        'wed' => 'Середа',
        'thu' => 'Четвер',
        'fri' => 'П’ятниця',
        'sat' => 'Субота',
        'sun' => 'Неділя',
    ];

    /**
     * Array containing dictionary names only used within the component.
     *
     * @var array
     */
    public array $dictionaryNames = [
        'DIVISION_TYPE',
        'SETTLEMENT_TYPE',
        'PHONE_TYPE',
        'DIVISION_TYPE'
    ];

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
        $this->divisionForm->notWorking($day, $allDayWork);
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
        $this->divisionForm->addAvailableShift($day);
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
        $this->divisionForm->deleteShift($day, $shift);
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
        $this->divisionForm->noShift($day, $isShift);
    }

    /**
     * Sets the dictionary for this component.
     *
     * @return static Returns the current instance for method chaining
     */
    protected function setDictionary(): static
    {
        $this->getDictionary();

        return $this;
    }

    /**
     * Filters an array of dictionaries based on allowed items.
     *
     * @param array $source The source array of dictionaries to filter
     * @param array $allowedItems Array of allowed items to filter by
     *
     * @return array The filtered array containing only allowed items
     */
    protected function filterDictionaries(array $source, array $allowedItems): array
    {
        $arr = [];

        foreach ($source as $key => $dictionary) {

            if (in_array($key, array_keys($allowedItems))) {
                $arr[$key] = array_filter($dictionary, fn($item) => in_array($item, $allowedItems[$key]), ARRAY_FILTER_USE_KEY);

                continue;
            }

            $arr[$key] = $dictionary;
        }

        return $arr;
    }
}
