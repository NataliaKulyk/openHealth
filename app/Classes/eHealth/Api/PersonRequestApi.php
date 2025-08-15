<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

class PersonRequestApi
{
    /**
     * Schema Crate/Update Person Request v2.
     *
     * @return array
     */
    public static function schemaRequest(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'properties' => [
                'person' => [
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
                        'birth_country' => [
                            'type' => 'string'
                        ],
                        'birth_settlement' => [
                            'type' => 'string'
                        ],
                        'gender' => [
                            'enum' => [
                                'MALE',
                                'FEMALE'
                            ]
                        ],
                        'email' => [
                            'type' => 'string'
                        ],
                        'no_tax_id' => [
                            'type' => 'boolean'
                        ],
                        'tax_id' => [
                            'type' => 'string'
                        ],
                        'secret' => [
                            'type' => 'string'
                        ],
                        'documents' => [
                            'type' => 'array'
                        ],
                        'addresses' => [
                            'type' => 'array'
                        ],
                        'phones' => [
                            'type' => 'array'
                        ],
                        'authentication_methods' => [
                            'type' => 'array'
                        ],
                        'unzr' => [
                            'type' => 'string'
                        ],
                        'emergency_contact' => [
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
                                'phones' => [
                                    'type' => 'array'
                                ]
                            ],
                            'required' => [
                                'first_name',
                                'last_name',
                                'phones'
                            ]
                        ],
                        'confidant_person' => [
                            'type' => 'object',
                            'properties' => [
                                'person_id' => [
                                    'type' => 'string'
                                ],
                                'documents_relationship' => [
                                    'type' => 'array'
                                ]
                            ],
                            'required' => [
                                'person_id',
                                'documents_relationship'
                            ]
                        ]
                    ],
                    'required' => [
                        'first_name',
                        'last_name',
                        'birth_date',
                        'birth_country',
                        'birth_settlement',
                        'gender',
                        'no_tax_id',
                        'secret',
                        'documents',
                        'addresses',
                        'emergency_contact'
                    ]
                ],
                'patient_signed' => [
                    'type' => 'boolean'
                ],
                'process_disclosure_data_consent' => [
                    'type' => 'boolean'
                ],
                'authorize_with' => [
                    'type' => 'string'
                ]
            ],
            'required' => [
                'person',
                'patient_signed',
                'process_disclosure_data_consent'
            ]
        ];
    }

    /**
     * Approve Person Request v2.
     *
     * @return array
     */
    public function approveSchemaRequest(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'properties' => [
                'verification_code' => [
                    'type' => 'number',
                ]
            ]
        ];
    }

    /**
     * Encrypt data for signing person request v2.
     *
     * @return array
     */
    public function encryptSignSchemaRequest(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'properties' => [
                'status' => [
                    'type' => 'string'
                ],
                'id' => [
                    'type' => 'string'
                ],
                'person' => [
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
                        'birth_country' => [
                            'type' => 'string'
                        ],
                        'birth_settlement' => [
                            'type' => 'string'
                        ],
                        'gender' => [
                            'enum' => [
                                'MALE',
                                'FEMALE'
                            ]
                        ],
                        'email' => [
                            'type' => 'string'
                        ],
                        'no_tax_id' => [
                            'type' => 'boolean'
                        ],
                        'tax_id' => [
                            'type' => 'string'
                        ],
                        'secret' => [
                            'type' => 'string'
                        ],
                        'documents' => [
                            'type' => 'array'
                        ],
                        'addresses' => [
                            'type' => 'array'
                        ],
                        'phones' => [
                            'type' => 'array'
                        ],
                        'authentication_methods' => [
                            'type' => 'array'
                        ],
                        'unzr' => [
                            'type' => 'string'
                        ],
                        'emergency_contact' => [
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
                                'phones' => [
                                    'type' => 'array'
                                ]
                            ],
                            'required' => [
                                'first_name',
                                'last_name',
                                'phones'
                            ]
                        ],
                        'confidant_person' => [
                            'type' => 'array'
                        ]
                    ],
                    'required' => [
                        'first_name',
                        'last_name',
                        'birth_date',
                        'birth_country',
                        'birth_settlement',
                        'gender',
                        'no_tax_id',
                        'secret',
                        'documents',
                        'addresses',
                        'emergency_contact'
                    ]
                ],
                'patient_signed' => [
                    'type' => 'boolean'
                ],
                'process_disclosure_data_consent' => [
                    'type' => 'boolean'
                ],
                'content' => [
                    'type' => 'string'
                ],
                'channel' => [
                    'const' => 'MIS'
                ]
            ],
            'required' => [
                'status',
                'id',
                'person',
                'patient_signed',
                'process_disclosure_data_consent',
                'content',
                'channel'
            ]
        ];
    }

    /**
     * Sign Person Request v2.
     *
     * @return array
     */
    public function signSchemaRequest(): array
    {
        return [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'type' => 'object',
            'properties' => [
                'signed_content' => [
                    'type' => 'string'
                ]
            ],
            'required' => [
                'signed_content'
            ]
        ];
    }
}
