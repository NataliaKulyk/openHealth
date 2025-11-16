<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use App\Classes\eHealth\EHealthResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class Auth extends EHealthRequest
{
    public function login(string $email, string $password): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateAccessToken(...));

        return $this->post('auth/login', [
            'token' => [
                'grant_type' => 'password',
                'email' => $email,
                'password' => $password,
                'client_id' => config('ehealth.api.mis_id'),
                'scope' => 'app:authorize'
            ]
        ]);
    }

    public function authorize(string $accessToken, string $scopes, string $legalEntityId): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateAuthorize(...));
        $this->withToken($accessToken);

        return $this->post('oauth/apps/authorize', [
            'app' => [
                'client_id' => $legalEntityId,
                'redirect_uri' => config('ehealth.api.redirect_uri'),
                'scope' => $scopes
            ]
        ]);
    }

    protected function validateAccessToken(EHealthResponse $response): array
    {
        $validator = Validator::make($response->getData(), [
            'value' => ['required', 'string'],
            'user_id' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }

    protected function validateAuthorize(EHealthResponse $response): array
    {
        $validator = Validator::make($response->getData(), [
            'details' => ['required', 'array'],
            'details.app_id' => ['required', 'uuid'],
            'details.client_id' => ['required', 'uuid', Rule::exists('legal_entities', 'uuid')],
            'details.redirect_uri' => ['required', 'url'],
            'details.scope_request' => ['required', 'string'],
            'expires_at' => ['required', 'integer'],
            'id' => ['required', 'uuid'],
            'name' => ['required', 'string', Rule::in(['authorization_code'])],
            'user_id' => ['required', 'uuid'],
            'value' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            Log::channel('e_health_errors')->error('Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return $validator->validate();
    }
}
