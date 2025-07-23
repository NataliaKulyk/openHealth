<?php

namespace App\Casts;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class LegalEntityArchiveCast implements CastsAttributes
{
    public const array KEYS_CAST_MAP = [
        'date' => 'setDate',
        'place' => 'setPlace'
    ];

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @return array
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (empty($value)) {
            return [];
        }

        return $this->proceedValueData($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     *
     * @return array
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        if (empty($value)) {
            return json_encode([]);
        }

        return json_encode($this->proceedValueData($value));
    }

    /**
     * Converts the given value data (string with jsons or an array) to an array.
     *
     * @param array $value The value to be converted.
     *
     * @return array The converted array.
     */
    protected function convertValueToArray(array $value): array
    {
        $arr = [];

        foreach($value as $jsonData) {
            $arr[] = is_array($jsonData) ? $jsonData : json_decode($jsonData, true) ?? [];
        }

        return $arr;
    }

    /**
     * Processes the provided value data and returns the resulting array.
     *
     * @param string|array $valueData The input data to be processed.
     *
     * @return array The processed data array.
     */
    protected function proceedValueData(string|array $value): array
    {
        $arrayData = is_array($value) ? $this->convertValueToArray($value) : json_decode($value, true) ?? [];

        $arr = [];

        foreach ($arrayData as $subArray) {
            foreach (self::KEYS_CAST_MAP as $key => $methodName) {
                if (! isset($subArray[$key])) {
                    continue;
                }

                if (!method_exists($this, $methodName)) {
                    throw new Exception("LegalEntityArchiveCast: method {$methodName} not found");
                }

                $subArray[$key] = !empty($subArray[$key]) ? $this->{$methodName}($subArray[$key]) : "";
            }

            $arr[] = $subArray;
        }

        return $arr;
    }

    /**
     * Sets the date value.
     *
     * @param string $value The date value to set.
     *
     * @return string The formatted or processed date value.
     */
    protected function setDate(string $value): string
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Sets the place value.
     *
     * @param string $value The place value to set.
     *
     * @return string The processed place value.
     */
    protected function setPlace(string $value): string
    {
        return $value;
    }
}
