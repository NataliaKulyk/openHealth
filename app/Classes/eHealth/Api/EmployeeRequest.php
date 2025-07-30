<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class EmployeeRequest extends EHealthRequest
{
    /**
     * The API endpoint for creating employee requests.
     */
    public const string ENDPOINT = '/api/v2/employee_requests';

    /**
     * Creates a request in eHealth, encapsulating the logic of sending and handling the response.
     * Returns an array with data on success or throws an exception on failure.
     *
     * @param  string  $signedContent The signed data payload.
     * @return array The response data from eHealth.
     * @throws RuntimeException If the response from eHealth contains an error.
     * @throws ConnectionException
     */
    public static function createFromSignedContent(string $signedContent): array
    {
        // Prepare the request body as per the eHealth specification.
        $requestBody = [
            'signed_content' => $signedContent,
            'signed_content_encoding' => 'base64',
        ];

        // 1. Instantiate the class and send the request.
        $response = (new static())->post(self::ENDPOINT, $requestBody);

        // 2. Check the response. If it's not successful, throw an exception with the error body.
        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = 'eHealth API Error (422): ' . ($errorBody['error']['message'] ?? $response->reason());

            Log::channel('e_health_errors')->error('EHealth Create EmployeeRequest Error', [
                'status' => $response->status(),
                'body' => $errorBody,
            ]);

            throw new RuntimeException($errorMessage);
        }

        // 3. On success, return the data from the response.
        // eHealth usually returns the main payload within the 'data' key.
        return $response->json('data', []);
    }

    /**
     * Validates the data array received from a successful eHealth response.
     *
     * @param  array  $responseData
     * @return array The validated data.
     * @throws ValidationException
     */
    public function validateCreateResponseFromArray(array $responseData): array
    {
        $mappedData = self::replaceEHealthPropNames($responseData);
        $validator = Validator::make($mappedData, [
            'uuid' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('eHealth EmployeeRequest Response Validation failed', $validator->errors()->all());
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Formats data from a revision into the structure required by the eHealth API.
     */
    public static function formatEHealthPayload(array $revisionData): array
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
                'issued_at' => isset($doc['issued_at']) && ! empty($doc['issued_at']) ? Carbon::parse($doc['issued_at'])->format('Y-m-d') : null,
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

    /**
     * Replaces property names from the eHealth response (e.g., id -> uuid)
     * to match the local database schema.
     */
    public static function replaceEHealthPropNames(array $properties): array
    {
        $replaced = [];
        foreach ($properties as $name => $value) {
            $replaced[match ($name) {
                'id' => 'uuid',
                'legal_entity_id' => 'legal_entity_uuid',
                default => $name,
            }] = $value;
        }

        return $replaced;
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
                'certificate_number' => ['type' => 'string', 'format' => 'date'],
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
