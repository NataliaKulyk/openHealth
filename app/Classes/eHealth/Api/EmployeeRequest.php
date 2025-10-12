<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use App\Models\Division;
use RuntimeException;

class EmployeeRequest extends EHealthRequest
{
    /**
     * The API endpoint for employee requests.
     */
    public const string ENDPOINT = '/api/v2/employee_requests';

    /**
     * Creates a new Employee Request in eHealth using a signed data payload.
     * This is the primary action method for this class.
     *
     * @param string $signedContent The base64 encoded signed string.
     *
     * @return array The response data from eHealth on success.
     * @throws ConnectionException|RuntimeException
     */
    public function create(string $signedContent): array
    {
        $requestBody = [ 'signed_content' => $signedContent, 'signed_content_encoding' => 'base64' ];

        $response = $this->post(self::ENDPOINT, $requestBody);

        return [
            'id' => $response->json('data.id'),
            'ehealth_response' => $response->json(),
        ];
    }

    /**
     * Transforms a source data array into a structured, partitioned array.
     *
     * This method takes a source array, typically from a Revision's data,
     * and reshapes it into a consistent structure with keys like 'employee',
     * 'party', 'documents', etc., making it ready for repository processing.
     *
     * @param array $sourceData The source data array containing all necessary information.
     * @return array A structured array partitioned into logical keys.
     */
    public function mapCreate(array $sourceData): array
    {

        $partyData = $sourceData['party'] ?? [];
        $doctorData = $sourceData['doctor'] ?? [];

        return [
            'employee' => Arr::get($sourceData, 'employee_request_data', []),
            'party' => Arr::except($partyData, ['documents', 'phones']),
            'documents' => $sourceData['documents'] ?? [],
            'phones' => $sourceData['phones'] ?? [],
            'educations' => $doctorData['educations'] ?? [],
            'specialities' => $doctorData['specialities'] ?? [],
            'qualifications' => $doctorData['qualifications'] ?? [],
            'science_degree' => $doctorData['science_degree'] ?? null,
        ];
    }

    /**
     * Builds the eHealth-compliant payload from the application's internal data structure.
     *
     * @param array $nestedData Data from the Revision model.
     * @return array The structured payload ready for signing and sending to eHealth.
     */
    public function schemaCreate(array $nestedData): array
    {
        $localDivisionId = Arr::get($nestedData, 'employee_request_data.division_id');
        $divisionUuid = $localDivisionId ? Division::find($localDivisionId)?->uuid : null;

        $partyPayload = Arr::only($nestedData['party'] ?? [], [
            'first_name', 'last_name', 'second_name', 'birth_date', 'gender',
            'tax_id', 'email', 'about_myself'
        ]);

        $partyPayload['no_tax_id'] = (bool) Arr::get($nestedData, 'party.no_tax_id');
        $partyPayload['working_experience'] = (int) Arr::get($nestedData, 'party.working_experience');
        $partyPayload['documents'] = $nestedData['documents'] ?? [];
        $partyPayload['phones'] = $nestedData['phones'] ?? [];

        $payload = [
            'position' => Arr::get($nestedData, 'employee_request_data.position'),
            'start_date' => Arr::get($nestedData, 'employee_request_data.start_date'),
            'end_date' => Arr::get($nestedData, 'employee_request_data.end_date'),
            'employee_type' => Arr::get($nestedData, 'employee_request_data.employee_type'),
            'division_id' => $divisionUuid,
            'legal_entity_id' => legalEntity()->uuid,
            'status' => 'NEW',
            'party' => $partyPayload,
        ];

        $doctorTypes = config('ehealth.doctors_type', []);
        $employeeType = Arr::get($nestedData, 'employee_request_data.employee_type');

        if (in_array($employeeType, $doctorTypes, true)) {
            $doctorData = Arr::get($nestedData, 'doctor');
            if (!empty($doctorData)) {
                $payloadKey = strtolower($employeeType);
                $payload[$payloadKey] = $doctorData;
            }
        }

        return ['employee_request' => $this->removeEmptyValuesRecursively($payload)];
    }

    /**
     * Recursively removes empty values from an array.
     */
    private function removeEmptyValuesRecursively(array $array): array
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                $value = $this->removeEmptyValuesRecursively($value);
            }
        }

        return array_filter($array, static function ($value) {
            return !is_null($value) && $value !== '' && $value !== [];
        });
    }


    public function schemaRequest(): array
    {
        $phoneDefinition = [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['MOBILE', 'LANDLINE'],
                ],
                'number' => [
                    'type' => 'string',
                    'pattern' => '^\+38[0-9]{10}$',
                ],
            ],
            'required' => ['type', 'number'],
            'additionalProperties' => false,
        ];

        $documentDefinition = [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['PASSPORT', 'NATIONAL_ID', 'BIRTH_CERTIFICATE', 'TEMPORARY_CERTIFICATE'],
                ],
                'number' => ['type' => 'string'],
            ],
            'required' => ['type', 'number'],
            'additionalProperties' => false,
        ];

        $educationDefinition = [
            'type' => 'object',
            'properties' => [
                'country' => ['type' => 'string', 'enum' => ['UA']],
                'city' => ['type' => 'string'],
                'institution_name' => ['type' => 'string'],
                'issued_date' => ['type' => 'string'],
                'diploma_number' => ['type' => 'string'],
                'degree' => ['type' => 'string', 'enum' => ['Молодший спеціаліст', 'Бакалавр', 'Спеціаліст', 'Магістр']],
                'speciality' => ['type' => 'string'],
            ],
            'required' => ['country', 'city', 'institution_name', 'diploma_number', 'degree', 'speciality'],
            'additionalProperties' => false,
        ];

        $qualificationDefinition = [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'enum' => ['Інтернатура', 'Спеціалізація', 'Передатестаційний цикл', 'Тематичне вдосконалення', 'Курси інформації', 'Стажування']],
                'institution_name' => ['type' => 'string'],
                'speciality' => ['type' => 'string'],
                'issued_date' => ['type' => 'string', 'format' => 'date'],
                'certificate_number' => ['type' => 'string'],
            ],
            'required' => ['type', 'institution_name', 'speciality'],
            'additionalProperties' => false,
        ];

        $specialityDefinition = [
            'type' => 'object',
            'properties' => [
                'speciality' => ['type' => 'string', 'enum' => ['Терапевт', 'Педіатр', 'Сімейний лікар']],
                'speciality_officio' => ['type' => 'boolean'],
                'level' => ['type' => 'string', 'enum' => ['Друга категорія', 'Перша категорія', 'Вища категорія']],
                'qualification_type' => ['type' => 'string', 'enum' => ['Присвоєння', 'Підтвердження']],
                'attestation_name' => ['type' => 'string'],
                'attestation_date' => ['type' => 'string', 'format' => 'date'],
                'valid_to_date' => ['type' => 'string', 'format' => 'date'],
                'certificate_number' => ['type' => 'string'],
            ],
            'required' => ['speciality', 'speciality_officio', 'level', 'qualification_type', 'attestation_name', 'certificate_number'],
            'additionalProperties' => false,
        ];

        $scienceDegreeDefinition = [
            'type' => 'object',
            'properties' => [
                'country' => ['type' => 'string', 'enum' => ['UA']],
                'city' => ['type' => 'string'],
                'degree' => ['type' => 'string', 'enum' => ['Доктор філософії', 'Кандидат наук', 'Доктор наук']],
                'institution_name' => ['type' => 'string'],
                'diploma_number' => ['type' => 'string'],
                'speciality' => ['type' => 'string', 'enum' => ['Терапевт', 'Педіатр', 'Сімейний лікар']],
                'issued_date' => ['type' => 'string', 'format' => 'date'],
            ],
            'required' => ['country', 'city', 'degree', 'institution_name', 'diploma_number', 'speciality'],
            'additionalProperties' => false,
        ];

        $partyDefinition = [
            'type' => 'object',
            'properties' => [
                'first_name' => ['type' => 'string'],
                'last_name' => ['type' => 'string'],
                'second_name' => ['type' => 'string'],
                'birth_date' => ['type' => 'string', 'format' => 'date'],
                'gender' => ['type' => 'string', 'enum' => ['MALE', 'FEMALE']],
                'no_tax_id' => ['type' => 'boolean'],
                'tax_id' => ['type' => 'string', 'pattern' => '^[1-9]([0-9]{7}|[0-9]{9})$'],
                'email' => ['type' => 'string', 'format' => 'email'],
                'working_experience' => ['type' => 'integer'],
                'about_myself' => ['type' => 'string'],
                'documents' => [
                    'type' => 'array',
                    'items' => $documentDefinition,
                ],
                'phones' => [
                    'type' => 'array',
                    'items' => $phoneDefinition,
                ],
            ],
            'required' => ['first_name', 'last_name', 'birth_date', 'gender', 'tax_id', 'email', 'documents', 'phones'],
            'additionalProperties' => false,
        ];

        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'definitions' => [
                'phone' => $phoneDefinition,
                'document' => $documentDefinition,
                'education' => $educationDefinition,
                'qualification' => $qualificationDefinition,
                'speciality' => $specialityDefinition,
                'science_degree' => $scienceDegreeDefinition,
                'party' => $partyDefinition,
            ],
            'type' => 'object',
            'properties' => [
                'employee_request' => [
                    'type' => 'object',
                    'properties' => [
                        'legal_entity_id' => ['type' => 'string', 'pattern' => '^[0-9a-f]{8}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{12}$'],
                        'division_id' => ['type' => 'string', 'pattern' => '^[0-9a-f]{8}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{12}$'],
                        'employee_id' => [
                            'type' => 'string',
                            'pattern' => '^[0-9a-f]{8}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{12}$',
                        ],
                        'position' => [
                            'type' => 'string',
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'format' => 'date',
                        ],
                        'end_date' => [
                            'type' => 'string',
                            'format' => 'date',
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'NEW',
                            ],
                        ],
                        'employee_type' => [
                            'type' => 'string',
                            'enum' => [
                                'DOCTOR',
                                'HR',
                                'ADMIN',
                                'OWNER',
                            ],
                        ],

                        'party' => $partyDefinition,
                        'doctor' => [
                            'type' => 'object',
                            'properties' => [
                                'educations' => [
                                    'type' => 'array',
                                    'items' => $educationDefinition,
                                ],
                                'qualifications' => [
                                    'type' => 'array',
                                    'items' => $qualificationDefinition,
                                ],
                                'specialities' => [
                                    'type' => 'array',
                                    'items' => $specialityDefinition,
                                ],
                                'science_degree' => $scienceDegreeDefinition,
                            ],
                            'required' => [
                                'educations',
                                'specialities',
                            ],
                        ],
                    ],
                    'required' => [
                        'legal_entity_id',
                        'position',
                        'start_date',
                        'status',
                        'employee_type',
                        'party',
                    ],
                ],
            ],
            'required' => [
                'employee_request',
            ],
        ];
    }
}
