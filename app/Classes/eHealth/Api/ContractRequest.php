<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use App\Classes\eHealth\EHealthResponse;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContractRequest extends EHealthRequest
{
    /**
     * The main endpoint for Contract Requests.
     */
    public const string URL = '/api/contract_requests';

    /**
     * Gets a list of contract requests from E-Health.
     * Corresponds to: GET /api/contract_requests/{contract_type}/?{filters...}
     *
     * @param  string  $contractType  'capitation' or 'reimbursement'.
     * @param  array  $filters  Associative array of filter parameters.
     * @param  int  $page  Page number.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getMany(string $contractType, array $filters, int $page = 1): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge(
            $this->options['query'] ?? [],
            $filters,
            ['page' => $page]
        );

        return $this->get(self::URL . '/' . $contractType, $mergedQuery);
    }

    /**
     * Gets the details of a single contract request from E-Health.
     * Corresponds to: GET /api/contract_requests/{id}
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getDetails(string $uuid): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateDetails(...));

        return $this->get(self::URL . '/' . $uuid);
    }

    /**
     * Initializes a contract request (Step 1).
     * Corresponds to: POST /api/contract_requests/{contract_type}
     *
     * @param  string  $contractType  'capitation' or 'reimbursement'.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function initialize(string $contractType): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateInitialize(...));

        return $this->post(self::URL . '/' . $contractType, []);
    }

    /**
     * Sends the signed contract request (Step 2).
     * Corresponds to: POST /api/contract_requests/{contract_type}/{id}
     *
     * @param  string  $uuid  The request UUID (from initializeRequest).
     * @param  string  $contractType  'capitation' or 'reimbursement'.
     * @param  array  $payload  The body with 'signed_content'.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException|EHealthValidationException|EHealthResponseException
     */
    public function create(string $uuid, string $contractType, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateCreate(...));

        return $this->post(self::URL . '/' . $contractType . '/' . $uuid, $payload);
    }

    /**
     * Assigns a contract request to an NHS employee.
     * Corresponds to: PATCH /api/contract_requests/{id}/actions/assign
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  array  $payload  Payload with 'assignee_id'.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function assign(string $uuid, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateAssign(...));

        return $this->patch(self::URL . '/' . $uuid . '/actions/assign', $payload);
    }

    /**
     * Approves a contract request (NHS action).
     * Corresponds to: PATCH /api/contract_requests/{id}/actions/approve
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  array  $payload  Payload with 'nhs_signer_id', etc.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function approve(string $uuid, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateApprove(...));

        return $this->patch(self::URL . '/' . $uuid . '/actions/approve', $payload);
    }

    /**
     * Declines a contract request (NHS action).
     * Corresponds to: PATCH /api/contract_requests/{id}/actions/decline
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  array  $payload  Payload with 'status_reason'.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function decline(string $uuid, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateDecline(...));

        return $this->patch(self::URL . '/' . $uuid . '/actions/decline', $payload);
    }

    /**
     * Approves a contract request (MSP action).
     * Corresponds to: PATCH /api/contract_requests/{contract_type}/{id}/actions/approve_msp
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  string  $contractType  'capitation' or 'reimbursement'.
     * @param  array  $payload  Signed payload.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function approveMsp(string $uuid, string $contractType, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateApproveMsp(...));

        return $this->patch(self::URL . '/' . $contractType . '/' . $uuid . '/actions/approve_msp', $payload);
    }

    /**
     * Terminates a contract.
     * Corresponds to: PATCH /api/contract_requests/{contract_type}/{id}/actions/terminate
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  string  $contractType  'capitation' or 'reimbursement'.
     * @param  array  $payload  Signed payload with reason.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function terminate(string $uuid, string $contractType, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateTerminate(...));

        return $this->patch(self::URL . '/' . $contractType . '/' . $uuid . '/actions/terminate', $payload);
    }

    /**
     * Gets the printable HTML content of a contract.
     * Corresponds to: GET /api/contract_requests/{contract_type}/{id}/printout_content
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  string  $contractType  'capitation' or 'reimbursement'.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getPrintoutContent(string $uuid, string $contractType): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validatePrintoutContent(...));

        return $this->get(self::URL . '/' . $contractType . '/' . $uuid . '/printout_content');
    }

    /**
     * Signs a contract (NHS action).
     * Corresponds to: PATCH /api/contract_requests/{id}/actions/sign_nhs
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  array  $payload  Signed payload.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function signNhs(string $uuid, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateSignNhs(...));

        return $this->patch(self::URL . '/' . $uuid . '/actions/sign_nhs', $payload);
    }

    /**
     * Signs a contract (MSP action).
     * Corresponds to: PATCH /api/contract_requests/{contract_type}/{id}/actions/sign_msp
     *
     * @param  string  $uuid  The UUID of the contract request.
     * @param  string  $contractType  'capitation' or 'reimbursement'.
     * @param  array  $payload  Signed payload.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function signMsp(string $uuid, string $contractType, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateSignMsp(...));

        return $this->patch(self::URL . '/' . $contractType . '/' . $uuid . '/actions/sign_msp', $payload);
    }

    // =================================================================
    // RESPONSE VALIDATORS
    // =================================================================

    /**
     * Validates the response for getMany().
     */
    protected function validateMany(EHealthResponse $response): array
    {
        $transformedData = [];
        foreach ($response->getData() as $item) {
            $transformedData[] = self::replaceEHealthPropNames($item);
        }

        // Validate filters based on user's URL example
        $validator = Validator::make($transformedData, [
            '*.uuid' => 'required|uuid',
            '*.status' => 'required|string',
            '*.contract_number' => 'required|string',
            '*.contractor_legal_entity_id' => 'sometimes|uuid',
            '*.contractor_owner_id' => 'sometimes|uuid',
            '*.nhs_signer_id' => 'sometimes|uuid',
            '*.edrpou' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (getMany) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for getDetails().
     */
    protected function validateDetails(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());

        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string',
            'contract_number' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'contractor_legal_entity' => 'required|array',
            'contractor_legal_entity.uuid' => 'required|uuid',
            'contractor_owner' => 'required|array',
            'contractor_owner.uuid' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (getDetails) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for initializeRequest().
     */
    protected function validateInitialize(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());

        $validator = Validator::make($transformedData, [
            'additional_document_url' => ['required', 'url'],
            'id' => ['required', 'uuid'],
            'statute_url' => ['required','url']
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (initializeRequest) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for createSignedRequest().
     */
    protected function validateCreate(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());

        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string',
            'contract_number' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'contractor_legal_entity' => 'required|array',
            'contractor_legal_entity.uuid' => 'required|uuid',
            'contractor_owner' => 'required|array',
            'contractor_owner.uuid' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (createSignedRequest) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for assign().
     */
    protected function validateAssign(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());
        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:ASSIGNED',
            'assignee_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (assign) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for approve().
     */
    protected function validateApprove(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());
        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:APPROVED',
            'nhs_signer_id' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (approve) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for decline().
     */
    protected function validateDecline(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());
        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:DECLINED',
            'status_reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (decline) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for approveMsp().
     */
    protected function validateApproveMsp(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());
        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:MSP_APPROVED',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (approveMsp) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for terminate().
     */
    protected function validateTerminate(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());
        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:TERMINATED',
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (terminate) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for getPrintoutContent().
     */
    protected function validatePrintoutContent(EHealthResponse $response): array
    {
        // This response is not JSON, it's { "content": "...", "encoding": "..." }
        $data = $response->getData();
        $validator = Validator::make($data, [
            'content' => 'required|string',
            'encoding' => 'required|string|in:base64,utf-8',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (getPrintoutContent) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for signNhs().
     */
    protected function validateSignNhs(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());
        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:SIGNED_NHS',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (signNhs) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Validates the response for signMsp().
     */
    protected function validateSignMsp(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());
        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:SIGNED',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (signMsp) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * Replaces eHealth property names with application-specific names (e.g., id -> uuid).
     * This logic is copied from Employee.php for robust nested replacement.
     *
     * @param  array  $properties  Raw properties from a single API item.
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
                case 'contractor_legal_entity':
                case 'contractor_owner':
                case 'legal_entity':
                case 'division':
                    if (is_array($value) && isset($value['id'])) {
                        $value['uuid'] = $value['id'];
                        unset($value['id']);
                    }
                    $replaced[$name] = $value;
                    break;
                case 'external_contractors':
                    if (is_array($value)) {
                        $replaced[$name] = array_map(function ($item) {
                            if (is_array($item) && isset($item['id'])) {
                                $item['uuid'] = $item['id'];
                                unset($item['id']);
                            }
                            // Recurse for nested objects like 'divisions' inside
                            if (is_array($item) && isset($item['divisions'])) {
                                $item['divisions'] = array_map(function ($div) {
                                    if (is_array($div) && isset($div['id'])) {
                                        $div['uuid'] = $div['id'];
                                        unset($div['id']);
                                    }

                                    return $div;
                                }, $item['divisions']);
                            }

                            return $item;
                        }, $value);
                    } else {
                        $replaced[$name] = $value;
                    }
                    break;
                default:
                    $replaced[$name] = $value;
                    break;
            }
        }

        return $replaced;
    }
}
