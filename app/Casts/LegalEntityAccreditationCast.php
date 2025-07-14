<?php

namespace App\Casts;

use Exception;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class LegalEntityAccreditationCast implements CastsAttributes
{
    public const array ARCHIVE_KEYS = [
        'category' => 'setCategory',
        'issued_date' => 'setIssuedDate',
        'expiry_date' => 'setExpiryDate',
        'order_no' => 'setOrderNo',
        'order_date' => 'setOrderDate'
    ];

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $this->proceedValueData($value);
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return json_encode($this->proceedValueData($value));
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
        $arrayData = is_array($value) ? $value: json_decode($value, true) ?? [];

        foreach (self::ARCHIVE_KEYS as $key => $methodName) {
            if (! isset($arrayData[$key])) {
                continue;
            }

            if (!method_exists($this, $methodName)) {
                throw new Exception("LegalEntityArchiveCast: method {$methodName} not found");
            }

            // Proceess value stored in array depend on its key and method
            $arrayData[$key] = $this->{$methodName}($arrayData[$key]);
        }

        return $arrayData;
    }

    /**
     * Sets the category value.
     *
     * @param string $value The category value to set.
     *
     * @return string The processed category value.
     */
    protected function setCategory(string $value): string
    {
        return $value;
    }

    /**
     * Sets the issued_date value.
     *
     * @param string $value The issued_date value to set.
     *
     * @return string The formatted or processed issued_date value.
     */
    protected function setIssuedDate(string $value): string
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Sets the expiry_date value.
     *
     * @param string $value The expiry_date value to set.
     *
     * @return string The formatted or processed expiry_date value.
     */
    protected function setExpiryDate(string $value): string
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    /**
     * Sets the order number value.
     *
     * @param string $value The order number value to set.
     *
     * @return string The processed order number value.
     */
    protected function setOrderNo(string $value): string
    {
        return $value;
    }

    /**
     * Sets the order_date value.
     *
     * @param string $value The order_date value to set.
     *
     * @return string The formatted or processed order_date value.
     */
    protected function setOrderDate(string $value): string
    {
        return Carbon::parse($value)->format('Y-m-d');
    }
}
