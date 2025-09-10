<?php

declare(strict_types=1);

namespace App\Livewire\Patient\Forms\Api;

use Illuminate\Support\Str;

class PatientRequestApi
{
    /**
     * Build an array of parameters for a patient request list.
     *
     * @param  array  $filters
     * @return array
     */
    public static function buildSearchForPerson(array $filters): array
    {
        foreach ($filters as $key => $filter) {
            $result[Str::snake($key)] = $filter;
        }

        self::removeEmptyKeys($result);

        return $result;
    }

    /**
     * Remove keys from an array if their values are empty strings.
     *
     * @param  array  $data
     * @return void
     */
    protected static function removeEmptyKeys(array &$data): void
    {
        foreach ($data as $key => &$value) {
            if (is_object($value)) {
                // Convert object to array
                $value = (array) $value;
                self::removeEmptyKeys($value);
                // Convert array back to object
                $value = (object) $value;
            } elseif (is_array($value)) {
                self::removeEmptyKeys($value);
            } elseif ($value === '') {
                unset($data[$key]);
            }
        }
    }
}
