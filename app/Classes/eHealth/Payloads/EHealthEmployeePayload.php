<?php

namespace App\Classes\eHealth\Payloads;

use App\Classes\eHealth\Api\EmployeeRequest as EHealthEmployeeRequest;
use Illuminate\Support\Carbon;

class EHealthEmployeePayload
{
    /**
     * Prepares, formats, and normalizes data for the eHealth employee request.
     *
     * @param array $revisionData The raw data from the local revision.
     * @return array The normalized payload ready for signing.
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

        $apiEmployeeRequest = [
            'position' => $employeeData['position'] ?? null,
            'status' => 'NEW',
            'employee_type' => $employeeData['employee_type'] ?? null,
            'legal_entity_id' => (string) legalEntity()->id,
            'start_date' => isset($employeeData['start_date']) ? Carbon::parse($employeeData['start_date'])->format('Y-m-d') : null,
        ];

        if (! empty($employeeData['division_id'])) {
            $apiEmployeeRequest['division_id'] = (string) $employeeData['division_id'];
        }

        if (! empty($employeeData['end_date'])) {
            $apiEmployeeRequest['end_date'] = Carbon::parse($employeeData['end_date'])->format('Y-m-d');
        }

        $apiEmployeeRequest['party'] = [
            'first_name' => $partyData['first_name'] ?? null,
            'last_name' => $partyData['last_name'] ?? null,
            'second_name' => $partyData['second_name'] ?? null,
            'birth_date' => isset($partyData['birth_date']) ? Carbon::parse($partyData['birth_date'])->format('Y-m-d') : null,
            'gender' => $partyData['gender'] ?? null,
            'no_tax_id' => (bool) ($partyData['no_tax_id'] ?? false),
            'tax_id' => $partyData['tax_id'] ?? null,
            'email' => $partyData['email'] ?? null,
            'working_experience' => isset($partyData['working_experience']) ? (int) $partyData['working_experience'] : null,
            'about_myself' => $partyData['about_myself'] ?? null,
            'phones' => array_map(fn ($phone) => ['type' => $phone['type'], 'number' => $phone['number']], $phonesData),
            'documents' => array_map(fn ($doc) => [
                'type' => $doc['type'],
                'number' => $doc['number'],
                'issued_by' => $doc['issued_by'] ?? null,
                'issued_at' => ! empty($doc['issued_at']) ? Carbon::parse($doc['issued_at'])->format('Y-m-d') : null,
            ], $documentsData),
        ];

        if (($employeeData['employee_type'] ?? null) === 'DOCTOR' && ! empty($doctorData)) {
            $doctorPayload = [];
            if (! empty($doctorData['educations'])) {
                $doctorPayload['educations'] = $doctorData['educations'];
            }
            if (! empty($doctorData['qualifications'])) {
                $doctorPayload['qualifications'] = $doctorData['qualifications'];
            }
            if (! empty($doctorData['specialities'])) {
                $doctorPayload['specialities'] = $doctorData['specialities'];
            }
            if (! empty($doctorData['science_degrees'])) {
                $doctorPayload['science_degree'] = $doctorData['science_degrees'][0];
            }
            if (! empty($doctorPayload)) {
                $apiEmployeeRequest['doctor'] = $doctorPayload;
            }
        }

        return ['employee_request' => $apiEmployeeRequest];
    }
}
