<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Models\User;
use App\Models\LegalEntity;
use App\Classes\eHealth\Request;
use App\Classes\eHealth\Exceptions\ApiException;
use Illuminate\Support\Facades\Session;
use Spatie\Permission\Models\Role;

class EmployeeApi
{
    public const URL_REQUEST = '/api/employee_requests';
    public const URL_REQUEST_MIS = '/api/mis/employee_requests';
    public const URL_REQUEST_V2 = '/api/v2/employee_requests';

    public const URL = '/api/employees';

    public static function _get($params): array
    {
        return new Request('GET', self::URL, $params)->sendRequest();
    }

    public static function _create($params = []): array
    {
        return new Request('POST', self::URL_REQUEST_V2, $params)->sendRequest();
    }

    public static function _dismissed($id): array
    {
        return new Request('PATCH', self::URL.'/'.$id.'/actions/deactivate', [])->sendRequest();
    }

    public static function _getById($id)
    {
        return new Request('GET', self::URL.'/'.$id, [])->sendRequest();
    }

    public static function _getRequestList($data): array
    {
        return new Request('GET', self::URL_REQUEST, $data)->sendRequest();
    }

    public static function _getRequestById($id): array
    {
        return new Request('GET', self::URL_REQUEST.'/'.$id, [])->sendRequest();
    }

    public static function _getRequestByIdMis($id): array
    {
        return new Request('GET', self::URL_REQUEST_MIS.'/'.$id, [])->sendRequest();
    }

    public function getApikey(): string
    {
        return config('ehealth.api.api_key');
    }

    /**
     * Authenticate user with eHealth
     *
     * @param  string  $code
     * @param  string  $legalEntityUUID
     * @return mixed
     * @throws ApiException
     */
    public static function authenticate(string $code, string $legalEntityUUID): mixed
    {
        $user = User::find(Session::get(config('ehealth.api.auth_ehealth')));
        $legalEntity = LegalEntity::whereUuid($legalEntityUUID)->first();

        if (!$user) {
            $role = Session::get('first_login_role');

            $permissions = Role::where('name', $role)
                ->whereGuardName('ehealth')
                ->firstOrFail()
                ->permissions()
                ->pluck('name')
                ->toArray();

            if ($legalEntity->type === LegalEntity::TYPE_PRIMARY_CARE) {
                $permissions = self::excludeContractPermissions($permissions);
            }

            $scope = implode(' ', $permissions);
        } else {
            $scope = $user->getScopes($legalEntity->clientId);
        }

        $data = [
            'token' => [
                'client_id' => $legalEntity->client_id ?? '',
                'client_secret' => $legalEntity->client_secret ?? '',
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('ehealth.api.redirect_uri'),
                'scope' => $scope
            ]
        ];

        return new Request('POST', config('ehealth.api.oauth.tokens'), $data, false)->sendRequest();
    }

    /**
     * @throws ApiException
     */
    public static function getUserDetails(): array
    {
        return new Request('GET', config('ehealth.api.oauth.user'), [])->sendRequest();
    }

    /**
     * Get a list of employees from E-Health with pagination and optional filters.
     *
     * @param array $filters An associative array of query parameters to filter the results.
     *
     * @return array
     * @throws ApiException
     */
    public static function getEmployeesList(array $filters): array
    {
        $employees = [];
        $page = 1;
        $perPage = config('ehealth.api.page_size', 150);
        $totalPages = 1;

        while ($page <= $totalPages) {
            $queryParams = array_merge($filters, [
                'page' => $page,
                'page_size' => $perPage
            ]);

            $response = new Request('GET', self::URL, $queryParams)->sendRequest();

            if (isset($response['data']) && is_array($response['data'])) {
                array_push($employees, ...$response['data']);
            }

            $totalPages = $response['paging']['total_pages'] ?? 1;
            $page++;
        }

        if (count($employees) > 1) {
            $ownerIndex = array_search('OWNER', array_column($employees, 'employee_type'), true);
            if ($ownerIndex !== false) {
                $tmp = $employees[$ownerIndex];
                $employees[$ownerIndex] = $employees[0];
                $employees[0] = $tmp;
            }
        }

        return $employees;
    }

    /**
     * Retrieve Employee Details by it's uuid
     *
     * @param string $employeeId
     *
     * @return array
     * @throws ApiException
     */
    public static function getEmployeeData(string $employeeId): array
    {
        $url = config('ehealth.api.domain') . "/api/employees/$employeeId";

        return new Request('GET', $url, [])->sendRequest();
    }

    /**
     * Retrieve EmployeeRequest Details by it's uuid
     *
     * @param  string  $requestId
     * @return array
     * @throws ApiException
     */
    public static function getEmployeeRequeestData(string $requestId): array
    {
        $url = config('ehealth.api.domain') . "/api/employee_requests/$requestId";

        return new Request('GET', $url, [])->sendRequest();
    }

    private static function excludeContractPermissions(array $permissions): array
    {
        $contractPermissions = [
            'contract:write',
            'contract_request:approve',
            'contract_request:create',
            'contract_request:sign',
            'contract_request:terminate',
        ];

        return array_diff($permissions, $contractPermissions);
    }

    public static function schemaRequest(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-04/schema#',
            'definitions' => [
                'phone' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => [
                                'MOBILE',
                                'LANDLINE'
                            ]
                        ],
                        'number' => [
                            'type' => 'string',
                            'pattern' => '^\+38[0-9]{10}$'
                        ]
                    ],
                    'required' => [
                        'type',
                        'number'
                    ],
                    'additionalProperties' => false
                ],
                'document' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => [
                                'PASSPORT',
                                'NATIONAL_ID',
                                'BIRTH_CERTIFICATE',
                                'TEMPORARY_CERTIFICATE'
                            ]
                        ],
                        'number' => [
                            'type' => 'string'
                        ]
                    ],
                    'required' => [
                        'type',
                        'number'
                    ],
                    'additionalProperties' => false
                ],
                'education' => [
                    'type' => 'object',
                    'properties' => [
                        'country' => [
                            'type' => 'string',
                            'enum' => [
                                'UA'
                            ]
                        ],
                        'city' => [
                            'type' => 'string'
                        ],
                        'institution_name' => [
                            'type' => 'string'
                        ],
                        'issued_date' => [
                            'type' => 'string'
                        ],
                        'diploma_number' => [
                            'type' => 'string'
                        ],
                        'degree' => [
                            'type' => 'string',
                            'enum' => [
                                'Молодший спеціаліст',
                                'Бакалавр',
                                'Спеціаліст',
                                'Магістр'
                            ]
                        ],
                        'speciality' => [
                            'type' => 'string'
                        ]
                    ],
                    'required' => [
                        'country',
                        'city',
                        'institution_name',
                        'diploma_number',
                        'degree',
                        'speciality'
                    ],
                    'additionalProperties' => false
                ],
                'qualification' => [
                    'type' => 'object',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'enum' => [
                                'Інтернатура',
                                'Спеціалізація',
                                'Передатестаційний цикл',
                                'Тематичне вдосконалення',
                                'Курси інформації',
                                'Стажування'
                            ]
                        ],
                        'institution_name' => [
                            'type' => 'string'
                        ],
                        'speciality' => [
                            'type' => 'string'
                        ],
                        'issued_date' => [
                            'type' => 'string',
                            'format' => 'date'
                        ],
                        'certificate_number' => [
                            'type' => 'string',
                            'format' => 'date'
                        ]
                    ],
                    'required' => [
                        'type',
                        'institution_name',
                        'speciality'
                    ],
                    'additionalProperties' => false
                ],
                'speciality' => [
                    'type' => 'object',
                    'properties' => [
                        'speciality' => [
                            'type' => 'string',
                            'enum' => [
                                'Терапевт',
                                'Педіатр',
                                'Сімейний лікар'
                            ]
                        ],
                        'speciality_officio' => [
                            'type' => 'boolean'
                        ],
                        'level' => [
                            'type' => 'string',
                            'enum' => [
                                'Друга категорія',
                                'Перша категорія',
                                'Вища категорія'
                            ]
                        ],
                        'qualification_type' => [
                            'type' => 'string',
                            'enum' => [
                                'Присвоєння',
                                'Підтвердження'
                            ]
                        ],
                        'attestation_name' => [
                            'type' => 'string'
                        ],
                        'attestation_date' => [
                            'type' => 'string',
                            'format' => 'date'
                        ],
                        'valid_to_date' => [
                            'type' => 'string',
                            'format' => 'date'
                        ],
                        'certificate_number' => [
                            'type' => 'string'
                        ]
                    ],
                    'required' => [
                        'speciality',
                        'speciality_officio',
                        'level',
                        'qualification_type',
                        'attestation_name',
                        'certificate_number'
                    ],
                    'additionalProperties' => false
                ],
                'science_degree' => [
                    'type' => 'object',
                    'properties' => [
                        'country' => [
                            'type' => 'string',
                            'enum' => [
                                'UA'
                            ]
                        ],
                        'city' => [
                            'type' => 'string'
                        ],
                        'degree' => [
                            'type' => 'string',
                            'enum' => [
                                'Доктор філософії',
                                'Кандидат наук',
                                'Доктор наук'
                            ]
                        ],
                        'institution_name' => [
                            'type' => 'string'
                        ],
                        'diploma_number' => [
                            'type' => 'string'
                        ],
                        'speciality' => [
                            'type' => 'string',
                            'enum' => [
                                'Терапевт',
                                'Педіатр',
                                'Сімейний лікар'
                            ]
                        ],
                        'issued_date' => [
                            'type' => 'string',
                            'format' => 'date'
                        ]
                    ],
                    'required' => [
                        'country',
                        'city',
                        'degree',
                        'institution_name',
                        'diploma_number',
                        'speciality'
                    ],
                    'additionalProperties' => false
                ],
                'party' => [
                    'type' => 'object',
                    'properties' => [
                        'first_name' => [
                            'type' => 'string'
                        ],
                        'last_name' => [
                            'type' => 'string'
                        ],
                        'second_name' => [
                            'type' => 'string'
                        ],
                        'birth_date' => [
                            'type' => 'string',
                            'format' => 'date'
                        ],
                        'gender' => [
                            'type' => 'string',
                            'enum' => [
                                'MALE',
                                'FEMALE'
                            ]
                        ],
                        'tax_id' => [
                            'type' => 'string',
                            'pattern' => '^[1-9]([0-9]{7}|[0-9]{9})$'
                        ],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email'
                        ],
                        'documents' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/definitions/document'
                            ]
                        ],
                        'phones' => [
                            'type' => 'array',
                            'items' => [
                                '$ref' => '#/definitions/phone'
                            ]
                        ]
                    ],
                    'required' => [
                        'first_name',
                        'last_name',
                        'birth_date',
                        'gender',
                        'tax_id',
                        'email',
                        'documents',
                        'phones'
                    ],
                    'additionalProperties' => false
                ]
            ],
            'type' => 'object',
            'properties' => [
                'employee_request' => [
                    'type' => 'object',
                    'properties' => [
                        'legal_entity_uuid' => [
                            'type' => 'string',
                            'pattern' => '^[0-9a-f]{8}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{12}$'
                        ],
                        'division_uuid' => [
                            'type' => 'string',
                            'pattern' => '^[0-9a-f]{8}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{12}$'
                        ],
                        'employee_id' => [
                            'type' => 'string',
                            'pattern' => '^[0-9a-f]{8}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{4}(-?)[0-9a-f]{12}$'
                        ],
                        'position' => [
                            'type' => 'string'
                        ],
                        'start_date' => [
                            'type' => 'string',
                            'format' => 'date'
                        ],
                        'end_date' => [
                            'type' => 'string',
                            'format' => 'date'
                        ],
                        'status' => [
                            'type' => 'string',
                            'enum' => [
                                'NEW'
                            ]
                        ],
                        'employee_type' => [
                            'type' => 'string',
                            'enum' => [
                                'DOCTOR',
                                'HR',
                                'ADMIN',
                                'OWNER'
                            ]
                        ],
                        'party' => [
                            'type' => 'object',
                            'properties' => [
                                'items' => [
                                    '$ref' => '#/definitions/party'
                                ]
                            ]
                        ],
                        'doctor' => [
                            'type' => 'object',
                            'properties' => [
                                'educations' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/definitions/education'
                                    ]
                                ],
                                'qualifications' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/definitions/qualification'
                                    ]
                                ],
                                'specialities' => [
                                    'type' => 'array',
                                    'items' => [
                                        '$ref' => '#/definitions/speciality'
                                    ]
                                ],
                                'science_degree' => [
                                    'type' => 'object',
                                    'items' => [
                                        '$ref' => '#/definitions/science_degree'
                                    ]
                                ]
                            ],
                            'required' => [
                                'educations',
                                'specialities'
                            ]
                        ]
                    ],
                    'required' => [
                        'legal_entity_id',
                        'position',
                        'start_date',
                        'status',
                        'employee_type',
                        'party'
                    ]
                ]
            ],
            'required' => [
                'employee_request'
            ]
        ];
    }

    public static function schemaResponse(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string'
                ],
                'division_id' => [
                    'type' => 'string'
                ],
                'legal_entity_id' => [
                    'type' => 'string'
                ],
                'position' => [
                    'type' => 'string'
                ],
                'start_date' => [
                    'type' => 'string'
                ],
                'end_date' => [
                    'type' => 'string'
                ],
                'status' => [
                    'enum' => [
                        'NEW',
                        'REJECTED',
                        'EXPIRED',
                        'APPROVED'
                    ]
                ],
                'employee_type' => [
                    'type' => 'string'
                ],
                'party' => [
                    'type' => 'object',
                    'properties' => [
                        'id' => [
                            'type' => 'string'
                        ],
                        'first_name' => [
                            'type' => 'string'
                        ],
                        'last_name' => [
                            'type' => 'string'
                        ],
                        'second_name' => [
                            'type' => 'string'
                        ],
                        'birth_date' => [
                            'type' => 'string'
                        ],
                        'gender' => [
                            'type' => 'string'
                        ],
                        'no_tax_id' => [
                            'type' => 'boolean'
                        ],
                        'tax_id' => [
                            'type' => 'string'
                        ],
                        'email' => [
                            'type' => 'string'
                        ],
                        'documents' => [
                            'type' => 'array'
                        ],
                        'phones' => [
                            'type' => 'array'
                        ],
                        'working_experience' => [
                            'type' => 'number'
                        ],
                        'about_myself' => [
                            'type' => 'string'
                        ]
                    ],
                    'required' => [
                        'first_name',
                        'last_name',
                        'birth_date',
                        'gender'
                    ]
                ],
                'doctor' => [
                    'type' => 'object',
                    'properties' => [
                        'educations' => [
                            'type' => 'array'
                        ],
                        'qualifications' => [
                            'type' => 'array'
                        ],
                        'specialities' => [
                            'type' => 'array'
                        ],
                        'science_degree' => [
                            'type' => 'object',
                            'properties' => [
                                'country' => [
                                    'type' => 'string'
                                ],
                                'city' => [
                                    'type' => 'string'
                                ],
                                'degree' => [
                                    'type' => 'string'
                                ],
                                'institution_name' => [
                                    'type' => 'string'
                                ],
                                'diploma_number' => [
                                    'type' => 'string'
                                ],
                                'speciality' => [
                                    'type' => 'string'
                                ],
                                'issued_date' => [
                                    'type' => 'string'
                                ]
                            ],
                            'required' => [
                                'country',
                                'city',
                                'degree',
                                'institution_name',
                                'diploma_number',
                                'speciality'
                            ]
                        ]
                    ],
                    'required' => [
                        'educations',
                        'specialities'
                    ]
                ],
                'inserted_at' => [
                    'type' => 'string'
                ],
                'updated_at' => [
                    'type' => 'string'
                ]
            ],
            'required' => [
                'position',
                'status',
                'employee_type',
                'id',
                'inserted_at',
                'updated_at'
            ]
        ];
    }
}
