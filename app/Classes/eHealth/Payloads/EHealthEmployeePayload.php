<?php

namespace App\Classes\eHealth\Payloads;

use App\Classes\eHealth\Api\EmployeeRequest as EHealthEmployeeRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class EHealthEmployeePayload
{
    /**
     * @return array
     */
    public static function getKeyMap(): array
    {
        $camelCaseKeys = [
            'position', 'employeeType', 'legalEntityId', 'startDate', 'divisionId', 'endDate',
            'firstName', 'lastName', 'secondName', 'birthDate', 'gender', 'noTaxId', 'taxId',
            'email', 'workingExperience', 'aboutMyself', 'phones', 'documents', 'educations',
            'qualifications', 'specialities', 'scienceDegrees',
        ];

        $map = collect($camelCaseKeys)->mapWithKeys(function ($key) {
            return [$key => Str::snake($key)];
        })->all();

        $map['scienceDegrees'] = 'science_degree';

        return $map;
    }

    /**
     * @return array
     */
    public static function getReverseKeyMap(): array
    {
        return array_flip(self::getKeyMap());
    }

    /**
     * Prepares, formats, and normalizes data for the eHealth employee request.
     */
    public static function prepare(array $revisionData): array
    {
        $formattedPayload = self::format($revisionData);

        return schemaService()
            ->setDataSchema($formattedPayload, app(EHealthEmployeeRequest::class))
            ->requestSchemaNormalize()
            ->getNormalizedData();
    }

    /**
     * Formats data from a revision into the structure required by the eHealth API.
     */
    public static function format(array $revisionData): array
    {
        $employeeData = $revisionData['employee_request_data'];
        $partyData = $revisionData['party'];
        $documentsData = $revisionData['documents'];
        $phonesData = $revisionData['phones'];
        $doctorData = $revisionData['doctor'] ?? [];

        $apiEmployeeRequest = self::mapData($employeeData, ['position', 'employeeType', 'startDate', 'divisionId', 'endDate']);
        $apiEmployeeRequest['status'] = 'NEW';
        $apiEmployeeRequest['legal_entity_id'] = (string) legalEntity()->id;

        $apiEmployeeRequest['party'] = self::mapData($partyData, [
            'firstName', 'lastName', 'secondName', 'birthDate', 'gender', 'noTaxId',
            'taxId', 'email', 'workingExperience', 'aboutMyself'
        ]);

        $apiEmployeeRequest['party']['phones'] = array_map(fn($phone) => self::mapData($phone, ['type', 'number']), $phonesData);
        $apiEmployeeRequest['party']['documents'] = array_map(fn($doc) => self::mapData($doc, ['type', 'number', 'issuedBy', 'issuedAt']), $documentsData);

        if (($employeeData['employee_type'] ?? null) === 'DOCTOR' && ! empty($doctorData)) {
            $apiEmployeeRequest['doctor'] = self::mapData($doctorData, [
                'educations', 'qualifications', 'specialities', 'scienceDegrees'
            ]);

            if (! empty($doctorData['science_degrees'])) {
                $apiEmployeeRequest['doctor']['science_degree'] = $doctorData['science_degrees'][0];
            }
            unset($apiEmployeeRequest['doctor']['science_degrees']);
        }

        return ['employee_request' => Arr::where($apiEmployeeRequest, fn($value) => ! is_null($value))];
    }

    /**
     * A generic helper to map and format data based on a list of keys.
     */
    protected static function mapData(array $source, array $keys): array
    {
        $payload = [];
        $keyMap = self::getKeyMap();

        foreach ($keys as $key) {
            $eHealthKey = $keyMap[$key] ?? $key;
            $value = Arr::get($source, $key);

            if (is_null($value)) {
                continue;
            }

            // Special formatting for specific key types
            switch ($key) {
                case 'birthDate':
                case 'startDate':
                case 'endDate':
                case 'issuedAt':
                    $value = Carbon::parse($value)->format('Y-m-d');
                    break;
                case 'noTaxId':
                    $value = (bool) $value;
                    break;
                case 'workingExperience':
                    $value = (int) $value;
                    break;
            }

            $payload[$eHealthKey] = $value;
        }

        return $payload;
    }
}
