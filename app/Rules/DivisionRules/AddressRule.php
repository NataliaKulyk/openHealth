<?php

declare(strict_types=1);

namespace App\Rules\DivisionRules;

use Closure;
use App\Models\Division;
use App\Models\LegalEntity;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Exceptions\CustomValidationException;

class AddressRule implements ValidationRule
{
    /**
     * List of address validation rule method names to be checked.
     *
     * Each method in this list should return a boolean indicating whether the rule passes.
     * Used in the validate() method to sequentially check all address-related rules.
     *
     * @var array
     */
    public const array ADDRESS_RULES_LIST = [
        'checkAddressType',
        'checkSettlementType',
        'checkStreetType',
        'checkZipCode',
        'checkMapping'
    ];

    protected string $message;

    protected array $dictionaries;

    protected array $division;

    public function __construct(array $division)
    {
        $this->division = $division;
        $this->message = __('validation.attributes.healthcareService.error.division.address.commonError');
        $this->dictionaries = dictionary()->getDictionaries(['ADDRESS_TYPE', 'SETTLEMENT_TYPE', 'STREET_TYPE']);
    }

    /**
     * Check that all bunch of the address' data is correct and valid
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach (self::ADDRESS_RULES_LIST as $check) {
            if (!$this->$check()) {
                $this->throwError();
            }
        }
    }

    /**
     * Throw a custom validation exception with the current error message.
     *
     * This method is called when a address type rule fails validation.
     *
     * @return void
     *
     * @throws CustomValidationException
     */
    protected function throwError(): void
    {
        throw new CustomValidationException($this->message(), 'custom');
    }

    /**
     * Set the custom error message for the validation rule.
     *
     * This message will be used when throwing a validation exception.
     *
     * @param string $message The error message to set.
     *
     * @return void
     */
    protected function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Get the current error message for the validation rule.
     *
     * @return string The error message.
     */
    protected function message(): string
    {
        return $this->message;
    }

    /**
     * Check that addresses.type exists in dictionaries
     *
     * @return bool
     */
    protected function checkAddressType(): bool
    {
        $addressType = $this->division['addresses']['type'];

        if (!in_array($addressType, array_keys($this->dictionaries['ADDRESS_TYPE']))) {
            $this->setMessage(__('validation.attributes.healthcareService.error.division.address.type'));

            return false;
        }

        return true;
    }

    /**
     * Check that addresses.settlement_type exists in dictionaries
     *
     * @return bool
     */
    protected function checkSettlementType(): bool
    {
        $settlementType = $this->division['addresses']['settlementType'];

        if (!in_array($settlementType, array_keys($this->dictionaries['SETTLEMENT_TYPE']))) {
            $this->setMessage(__('validation.attributes.healthcareService.error.division.address.settlementType'));

            return false;
        }

        return true;
    }

    /**
     * Check that addresses.street_type exists in dictionaries
     *
     * @return bool
     */
    protected function checkStreetType(): bool
    {
        $streetType = $this->division['addresses']['streetType'];

        if (!in_array($streetType, array_keys($this->dictionaries['STREET_TYPE']))) {
            $this->setMessage(__('validation.attributes.healthcareService.error.division.address.streetType'));

            return false;
        }

        return true;
    }

    /**
     * Check that addresses.zip has no more than 5 digits
     *
     * @return bool
     */
    protected function checkZipCode(): bool
    {
        $zipCode = $this->division['addresses']['zip'];

        if (!empty($zipCode) && !preg_match('/^[0-9]{5}$/', $zipCode)) {
            $this->setMessage(__('validation.attributes.healthcareService.error.division.address.zip'));

            return false;
        }

        return true;
    }

    /**
     * Check mapping legal_entity_type, division_type and address_type and its obligation
     *
     * @return bool
     */
    protected function checkMapping(): bool
    {
        $legalEntityType = legalEntity()->type;
        $divisionType = $this->division['type'];
        $addressType = $this->division['addresses']['type'];

        if (in_array($divisionType, Division::getValidDivisionTypes()) &&
            in_array($legalEntityType, Division::getValidLegalEntityTypes()) &&
            $addressType === 'RESIDENCE'
        ) {
            return true;
        }

        $this->setMessage(__('validation.attributes.healthcareService.error.address.mapping'));

        return false;
    }
}
