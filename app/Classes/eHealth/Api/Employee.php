<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use App\Classes\eHealth\EHealthResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class Employee extends EHealthRequest
{
    public const string URL = '/api/employees';

    /**
     * Gets a single page of employees from E-Health.
     * It now attaches a validator to process the response.
     *
     * @param array $filters An associative array of query parameters to filter the results.
     * @param int   $page    The page number to fetch.
     *
     * @return PromiseInterface|EHealthResponse The EHealthResponse object containing the validated and transformed data.
     * @throws ConnectionException
     */
    public function getMany(array $filters, int $page = 1): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge(
            $this->options['query'] ?? [],
            $filters,
            ['page' => $page]
        );

        return $this->get(self::URL, $mergedQuery);
    }

    /**
     * Validates the response for a list of employees.
     *
     * @param EHealthResponse $response The response from the eHealth API.
     * @return array The validated and transformed data.
     */
    protected function validateMany(EHealthResponse $response): array
    {
        $transformedData = [];
        foreach ($response->getData() as $item) {
            $transformedData[] = self::replaceEHealthPropNames($item);
        }

        $validator = Validator::make($transformedData, [
            '*' => 'required|array',
            '*.uuid' => 'required|uuid',
            '*.status' => 'required|string',
            '*.position' => 'required|string',
            '*.employee_type' => 'required|string',
            '*.start_date' => 'required|date_format:Y-m-d',
            '*.end_date' => 'nullable|date_format:Y-m-d',
            '*.is_active' => 'required|boolean',
            '*.party' => 'required|array',
            '*.party.uuid' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error(
                'EHealth Employee validation failed: ' . implode(', ', $validator->errors()->all())
            );
            // Ви можете тут кинути виняток, щоб зупинити процес
            // throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Replaces eHealth property names with the ones used in the application (e.g., id -> uuid).
     *
     * @param array $properties Raw properties from a single API item.
     * @return array Properties with application-friendly names.
     */
    protected static function replaceEHealthPropNames(array $properties): array
    {
        $replaced = [];
        foreach ($properties as $name => $value) {
            switch ($name) {
                case 'id':
                    $replaced['uuid'] = $value;
                    break;
                case 'party':
                    $value['uuid'] = $value['id'];
                    unset($value['id']);
                    $replaced['party'] = $value;
                    break;
                case 'division':
                    if (is_array($value)) {
                        $value['uuid'] = $value['id'];
                        unset($value['id']);
                    }
                    $replaced['division'] = $value;
                    break;

                default:
                    $replaced[$name] = $value;
                    break;
            }
        }
        return $replaced;
    }
}
