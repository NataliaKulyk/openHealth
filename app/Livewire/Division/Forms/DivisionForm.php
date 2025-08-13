<?php

declare(strict_types=1);

namespace App\Livewire\Division\Forms;

use Log;
use App\Rules\Email;
use App\Traits\FormTrait;
use App\Rules\PhoneNumber;
use App\Rules\InDictionary;
use App\Rules\PhoneDuplicates;
use App\Classes\eHealth\EHealth;
use Livewire\Attributes\Validate;
use App\Rules\DivisionRules\TypeRule;
use App\Repositories\AddressRepository;
use App\Rules\DivisionRules\AddressRule;
use App\Rules\DivisionRules\LocationRule;
use App\Rules\DivisionRules\WorkingHoursRule;
use App\Exceptions\CustomValidationException;
use Livewire\Features\SupportFormObjects\Form;
use Illuminate\Validation\ValidationException;
use App\Rules\DivisionRules\LegalEntityStatusRule;

// TODO: (after divide DivisionForm onto three classes) rename this one to the DivisionForm
class DivisionForm extends Form
{
    use FormTrait;

    protected ?AddressRepository $addressRepository;

    #[Validate([
        'division.name' => 'required|min:6|max:255',
        'division.type' => 'required',
        'division.email' => ['required', 'email', new Email()],
        'division.addresses' => 'required',
    ])]
    public ?array $division = [];

    public function boot(AddressRepository $addressRepository)
    {
        $this->addressRepository = $addressRepository;
    }

    /**
     * Get the current division form data as an array.
     *
     * @return array The division form data.
     */
    public function getDivision(): array
    {
        return $this->division;
    }

    /**
     * Set the division's form data.
     * Replaces the current division's form data with the provided array.
     *
     * @param array $division The division data to set in the form.
     *
     * @return void
     */
    public function setDivision(array $division)
    {
        $this->division = $division;
    }

    /**
     * Returns the value of the specified parameter from the division's array,
     * or an empty string if the parameter does not exist.
     *
     * @param string $param The parameter name to retrieve.
     *
     * @return mixed The value of the parameter, or an empty string if not set.
     */
    public function getDivisionParam(string $param): mixed
    {
        return $this->division[$param] ?? '';
    }

    /**
     * Assigns the given value to the specified parameter in the division array.
     *
     * @param string $param The parameter name to set.
     * @param mixed $value The value to assign to the parameter.
     *
     * @return void
     */
    public function setDivisionParam(string $param, mixed $value): void
    {
        $this->division[$param] = $value;
    }

    /**
     * Check if the specified division parameter exists and its value is null or empty.
     *
     * This is mostly used for parameters 'work_hours' to determine if the key is present
     * in the division's array and its value is considered "empty" (null, empty string, or empty array).
     *
     * @param string $paramName
     *
     * @return bool
     */
    public function isDivisionParamExistAndNull(string $paramName): bool
    {
        return array_key_exists($paramName, $this->division) && !$this->division[$paramName];
    }

    /**
     * Remove the given parameter from the division's array if it exists.
     *
     * @param string $paramName
     * @return void
     */
    public function unsetDivisionParam(string $paramName)
    {
        unset($this->division[$paramName]);
    }

    /**
     * Do form's validation (check correctness of filling the form fields)
     *
     * @return mixed
     */
    public function doValidation(): string
    {
        $this->resetErrorBag();

        $this->division['addresses'] = $this->component->address;

        $errors = [];

        try {
            $errors = $this->component->addressValidation();

            $this->validate();

            if (!empty($errors)) {
                throw ValidationException::withMessages($errors);
            }
        } catch (ValidationException $err) {
            $errors = array_merge($err->errors(), $errors);

            // Throw an validation error from Division's side
            throw ValidationException::withMessages($errors);
        }

        $failMessage = $this->customRulesValidation();

        return $failMessage;
    }

    public function rules(): array
    {
        return [
            'division.external_id' => 'nullable|integer|gt:0',
            'division.location.longitude' => 'nullable|numeric|required_with:division.location.latitude',
            'division.location.latitude' => 'nullable|numeric|required_with:division.location.longitude',
            'division.phones.number' => ['required', 'string', new PhoneNumber()],
            'division.phones.type' => [
                'required',
                'string',
                new InDictionary('PHONE_TYPE'),
                new PhoneDuplicates($this->division['phones'])
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'division.location.longitude.required_with' => __('validation.attributes.healthcareService.error.division.location.longitude_required'),
            'division.location.latitude.required_with' => __('validation.attributes.healthcareService.error.division.location.latitude_required'),
            'division.external_id.integer' => __('validation.attributes.healthcareService.error.division.external_id'),
            'division.email.required' => __('validation.attributes.healthcareService.error.division.email.required'),
            'division.email.email' => __('validation.attributes.healthcareService.error.division.email.wrong'),
            'division.phones.type' => __('validation.attributes.healthcareService.error.division.phone.type_required'),
            'division.phones.number' => __('validation.attributes.healthcareService.error.division.phone.number_required')
        ];
    }

    /**
     * Working Hours may be not initiated (for creation case) or may be incomplete (for update case).
     * Here, this method will bring address array to properly state
     *
     * @return void
     */
    public function initWorkingHours(array $weekdays): void
    {
        $arr = !empty($this->division['working_hours']) ? $this->division['working_hours'] : [];

        foreach ($weekdays as $day => $name) {
            if (!isset($arr[$day]) || (!empty($arr[$day][0]) && $arr[$day][0]['0'] === '00:00' && $arr[$day][0]['1'] === '00:00')) {
                $arr[$day] = [[]];
            }
        }

        $this->division['working_hours'] = $arr;
    }

    /**
     * Proceed data when day is off and hasn't the schedule at all
     *
     * @param mixed $day
     * @param mixed $allDayWork
     *
     * @return void
     */
    public function notWorking($day, $allDayWork)
    {
        if ($allDayWork) {
            $this->division['working_hours'][$day] = [];
        } else {
            if (count($this->division['working_hours'][$day]) === 0) {
                $this->division['working_hours'][$day][] = [];
            }
        }
    }

    /**
     * Add shift(s) to the current day's schedule
     *
     * @param string $day
     *
     * @return void
     */
    public function addAvailableShift(string $day): void
    {
        $this->division['working_hours'][$day][] = [];
    }

    /**
     * Remove the selected shift from the day's schedule
     *
     * @param string $day   // key value aka 'mon', 'tue' etc.
     * @param int $shift    // shift's numeric position in array
     *
     * @return void
     */
    public function deleteShift(string $day, int $shift)
    {
        unset($this->division['working_hours'][$day][$shift]);

        // This need to recalculate numeric array keys (remove holes in numbering)
        $this->division['working_hours'][$day] = array_values($this->division['working_hours'][$day]);
    }

    /**
     * This method called when no shift should be present in the day's schedule.
     * But one time range must left anyway!
     *
     * @param mixed $day
     * @param mixed $isShift    // true if shift schedule is activated
     * @return void
     */
    public function noShift($day, $isShift)
    {
        if ($isShift) {
            $shiftCount = count($this->division['working_hours'][$day]);

            // There can be only one!
            if ($shiftCount > 1) {
                for ($i = 1; $i < $shiftCount; $i++) {
                    $this->deleteShift($day, $i);
                }
            }
        }
    }

    /**
     * Get the list of custom validation rules for the division form.
     *
     * These rules cover business logic validation such as:
     * - Legal entity status
     * - Location requirements for certain division types
     * - Address data validity
     * - Working hours schedule correctness
     * - Division type existence in dictionaries
     *
     * @return array An array of custom validation rule instances.
     */
    protected function customRules()
    {
        return [
            // Check that legal entity is in ‘ACTIVE’ or ‘SUSPENDED’ status
            new LegalEntityStatusRule(),
            // Check that location exists in request for legal entity with type PHARMACY
            new LocationRule($this->division),
            // Check that all bunch of the address' data is correct and valid
            new AddressRule($this->division),
            // Check that working hours schedule is correct
            new WorkingHoursRule($this->division),
            // Check that Division type exists in dictionaries
            new TypeRule($this->division),
        ];
    }

    /**
     * Rules for business-logic validation
     *
     * @return string
     */
    protected function customRulesValidation(): string
    {
        foreach ($this->customRules() as $rule) {
            try {
                $rule->validate('', '', fn () => null);
            } catch (CustomValidationException $e) {
                return $e->getMessage();
            }
        }

        return '';
    }
}
