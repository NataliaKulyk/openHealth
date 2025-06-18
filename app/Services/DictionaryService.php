<?php

declare(strict_types=1);

namespace App\Services;

use App\Classes\eHealth\Api\DictionaryApi;
use App\Classes\eHealth\Api\ServiceApi;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Livewire\Encounter\Forms\Api\EncounterRequestApi;
use App\Services\Dictionary\Dictionary;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DictionaryService
{
    /**
     * Local storage for all founded Dictionaries into incoming array.
     * As 'Dictionary' here should be interpreted as object created from the associative array.
     * Also, must be present the key that pointed to.
     *
     * @var Dictionary $rootDictionary
     */
    protected Dictionary $rootDictionary;

    public function __construct()
    {
        $this->rootDictionary = new Dictionary();
        $this->update();
    }

    /**
     * Update the data received from aHealth API.
     * This method filled the $rootDictionary with all founded data.
     *
     * @return void
     */
    protected function update(): void
    {
        $dictionaries = $this->getSourceDictionaries();

        foreach ($dictionaries as $entity) {
            if (empty($entity['name'])) {
                continue;
            }

            $key = $entity['name'];
            unset($entity['name']);

            $this->rootDictionary->setValue($key, $entity['values']);
        }
    }

    /**
     * Get all dictionaries data from external resource via API and put it into the cache.
     *
     * @return array
     * @throws RuntimeException
     */
    protected function getSourceDictionaries(): array
    {
        return Cache::remember('dictionaries', now()->addDays(7), static function (): array {
            try {
                return DictionaryApi::getDictionaries();
            } catch (ApiException $e) {
                Log::channel('e_health_errors')->error('Failed to fetch dictionaries', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                throw new RuntimeException();
            }
        });
    }

    /**
     * Find and return (if successfully) array of the Dictionaries.
     * If $toArray is set to TRUE then method return an array instead of the collection.
     *
     * @param  array  $searchArray  Name of Dictionary
     * @param  bool  $toArray  Flag indicates to return Dictionary as Array
     * @return Dictionary|array
     */
    public function getDictionaries(array $searchArray, bool $toArray = true): Dictionary|array
    {
        $items = [];

        foreach ($searchArray as $value) {
            if (isset($this->rootDictionary[$value])) {
                $items[$value] = collect($this->rootDictionary[$value])
                    ->mapWithKeys(static fn (array $item) => [$item['code'] => $item['description']]);
            }
        }

        return $toArray ? collect($items)->toArray() : new Dictionary($items);
    }

    /**
     * Get values by dictionary name.
     *
     * @param  string  $name
     * @param  bool  $toArray
     * @return Dictionary|array
     */
    public function getDictionary(string $name, bool $toArray = true): Dictionary|array
    {
        $items = [];

        if (isset($this->rootDictionary[$name])) {
            $items = collect($this->rootDictionary[$name])
                ->mapWithKeys(static fn (array $item) => [$item['code'] => $item['description']]);
        }

        return $toArray ? collect($items)->toArray() : new Dictionary($items);
    }

    /**
     * In order to get values that belong to a large reference dictionary, we must pass the name of the dictionary in the name parameter.
     *
     * @param  string  $name
     * @param  bool  $toArray
     * @return Dictionary|array
     * @throws ApiException
     */
    public function getLargeDictionary(string $name, bool $toArray = true): Dictionary|array
    {
        $items = $this->rootDictionary[$name];

        $formatted = [
            $name => $items
                ->filter(static fn (array $value) => isset($value['code'], $value['description']))
                ->mapWithKeys(static fn (array $value) => [
                    $value['code'] => [
                        'description' => $value['description'],
                        'is_active' => $value['is_active'],
                        'child_values' => $value['child_values']
                    ]
                ])
                ->toArray()
        ];

        return $toArray ? $formatted : new Dictionary($formatted);
    }

    /**
     * Receives a dictionary of services with caching for 7 days
     *
     * @return array
     */
    public function getServiceDictionary(): array
    {
        $serviceDictionary = Cache::remember('service_dictionary', now()->addDays(7), static function (): array {
            try {
                $params = EncounterRequestApi::buildGetServicesDictionary(pageSize: 300);

                return ServiceApi::getServiceDictionary($params);
            } catch (Exception $e) {
                Log::channel('e_health_errors')->error('Failed to fetch service dictionary', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);

                throw new RuntimeException();
            }
        });

        return collect($serviceDictionary)->flatMap(function (array $item) {
            return $this->flattenServiceItem($item);
        })->all();
    }

    /**
     * Recursively aligns the structure of the service element.
     *
     * @param  array  $item
     * @return array
     */
    protected function flattenServiceItem(array $item): array
    {
        $result = [];

        $result[] = [
            'code' => $item['code'],
            'name' => $item['name'],
            'id' => $item['id'],
            'category' => $item['category'] ?? null
        ];

        // Process groups if they exist
        if (isset($item['groups'])) {
            foreach ($item['groups'] as $group) {
                foreach ($this->flattenServiceItem($group) as $flattened) {
                    $result[] = $flattened;
                }
            }
        }

        // Process services if they exist
        if (isset($item['services'])) {
            foreach ($item['services'] as $service) {
                foreach ($this->flattenServiceItem($service) as $flattened) {
                    $result[] = $flattened;
                }
            }
        }

        return $result;
    }
}
