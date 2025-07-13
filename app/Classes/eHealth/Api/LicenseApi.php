<?php

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\Request;
use App\Core\Arr;
use App\Rules\InDictionary;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class LicenseApi extends Request
{
    public const URL = '/api/licenses';

    public static function _get(array $params = []): array
    {
        $result = (new Request('GET', self::URL, $params))->sendRequest();
        return self::validateAll($result);
    }

    public static function _create(array $params = []): array
    {
        return (new Request('POST', self::URL, $params))->sendRequest();
    }

    public static function _update(string $id, array $params = []): array
    {
        return (new Request('PATCH', self::URL . '/' . $id, $params))->sendRequest();
    }

    /**
     * validate get licenses input,
     * see: https://esoz.docs.apiary.io/#reference/administration/get-licenses
     */
    protected static function validateAll(array $result): array
    {
        $replaced = [];
        foreach ($result as $data) {
            $replaced[] = self::replaceEHealthPropNames($data);
        }

        $validator = Validator::make($replaced, [
            '*' => 'required|array',
            '*.active_from_date' => 'required|date_format:Y-m-d',
            '*.expiry_date' => 'required|date_format:Y-m-d',
            '*.uuid' => 'required|uuid',
            '*.is_active' => 'required|boolean',
            '*.is_primary' => 'required|boolean',
            '*.issued_by' => 'required|string',
            '*.issued_date' => 'required|date_format:Y-m-d',
            '*.issuer_status' => 'sometimes|string|nullable',
            '*.legal_entity_uuid' => ['required', 'uuid', Rule::in([legalEntity()->uuid])],
            '*.license_number' => 'required|string',
            '*.order_no' => 'required|string',
            '*.type' => ['required', 'string', new InDictionary('LICENSE_TYPE')],
            '*.what_licensed' => 'required|string',
            '*.ehealth_inserted_at' => 'required|date',
            '*.ehealth_inserted_by' => 'required|uuid',
            '*.ehealth_updated_at' => 'required|date',
            '*.ehealth_updated_by' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    /**
     * Replace eHealth property names with the ones used in the application.
     * E.g., id => uuid.
     */
    protected static function replaceEHealthPropNames(array $properties): array
    {
        $replaced = [];

        foreach ($properties as $name => $value) {
            switch ($name) {
                case 'id':
                    $replaced['uuid'] = $value;
                    break;
                case 'legal_entity_id':
                    $replaced['legal_entity_uuid'] = $value;
                    break;
                case 'inserted_at':
                    $replaced['ehealth_inserted_at'] = $value;
                    break;
                case 'inserted_by':
                    $replaced['ehealth_inserted_by'] = $value;
                    break;
                case 'updated_at':
                    $replaced['ehealth_updated_at'] = $value;
                    break;
                case 'updated_by':
                    $replaced['ehealth_updated_by'] = $value;
                    break;
                default:
                    $replaced[$name] = $value;
                    break;
            }
        }

        return $replaced;
    }
}
