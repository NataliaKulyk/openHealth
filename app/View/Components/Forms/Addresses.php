<?php

declare(strict_types=1);

namespace App\View\Components\Forms;

use Illuminate\View\Component;
use App\Classes\eHealth\Api\AdressesApi;

abstract class Addresses extends Component
{
    public bool $readonly;
    public array $address = [];

    public ?array $regions = [];

    public array $districts = [];

    public ?array $settlements = [];

    public ?array $streets = [];

    public string $class = '';

    public ?array $dictionaries;

    /**
     * Create a new component instance.
     */
    public function __construct($address, $districts, $settlements, $streets, $class, $readonly = false)
    {
        $this->readonly = $readonly;

        $this->address = $address;

        $this->regions = AdressesApi::_regions()['data'] ?? [];

        $this->districts = $districts;

        $this->settlements = $settlements;

        $this->streets = $streets;

        $this->class = $class;

        $this->dictionaries = dictionary()->getDictionaries(['SETTLEMENT_TYPE', 'STREET_TYPE']);
    }

    abstract public static function getAddressRules(array $address): array;

    abstract public static function getAddressMessages(): array;
}
