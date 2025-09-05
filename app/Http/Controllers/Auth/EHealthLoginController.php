<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\EHealth\Services\TokenStorage;
use App\Classes\eHealth\Exceptions\ApiException;
use App\Events\EHealthUserLogin;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\LegalEntity;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use App\Classes\eHealth\Api\EmployeeApi;
use App\Models\Employee\EmployeeRequest;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Classes\eHealth\Request as EHealthRequest;
use Illuminate\Contracts\Validation\Validator as ResponseValidator;
use Illuminate\Validation\Rule;

class EHealthLoginController extends Controller
{
    /**
     * This method is called when the user is redirected back from eHealth after it's successful authentication
     *
     * @return null|RedirectResponse
     */
    public function __invoke(Request $request): ?RedirectResponse
    {
        // get the email entered by the user in the login form
        $sessionEmail = session()->pull('selected_email');
        $testUser = $sessionEmail && in_array($sessionEmail, config('ehealth.test.emails'));

        /* exchange code to token */
        if (
            (config('ehealth.api.callback_prod') === false) &&
            // Pass certain emails anyway for testing purposes
            !$testUser
        ) {
            $code = $request->input('code');
            $url = 'http://localhost/ehealth/oauth?code=' . $code;

            return redirect($url);
        }

        if (!$request->has('code')) {
            return Redirect::route('login');
        }

        $selectedLegalEntityUuidFromSession = session()->pull('selected_legal_entity_uuid_for_ehealth');

        if (!$selectedLegalEntityUuidFromSession) {
            Log::warning('Legal Entity is not selected');

            return $this->breakAuth('auth.login.error.legal_entity_identity');
        }

        $eHealthTokenResponseData = $this->sendEHealthTokenRequest($request, $selectedLegalEntityUuidFromSession);

        if (empty($eHealthTokenResponseData)) {
            return Redirect::route('login')->with('error', __('auth.login.error.user_identity'));
        }

        $validator = $this->validateEHealthTokenResponse($eHealthTokenResponseData);

        if ($validator->fails()) {
            Log::error(__('auth.login.error.validation.auth', [], 'en'), ['errors' => $validator->errors()]);
            return Redirect::route('login')->with('error', __('auth.login.error.validation.auth'));
        }

        $validatedEHealthTokenData = $validator->validated();

        app(TokenStorage::class)->store($validatedEHealthTokenData);

        $authUserUUID = $validatedEHealthTokenData['user_id'];
        $authLegalEntityUUID = $validatedEHealthTokenData['details']['client_id'];

        /* This checks if the user chose one LE, but eHealth returned another */
        if ($selectedLegalEntityUuidFromSession !== $authLegalEntityUUID) {
            Log::warning('User selected a different Legal Entity in form than eHealth returned.', [
                'Selected in form' => $selectedLegalEntityUuidFromSession,
                'Returned by eHealth' => $authLegalEntityUUID,
                'User UUID' => $authUserUUID,
            ]);

            return $this->breakAuth('auth.login.error.legal_entity_identity');
        }

        $legalEntity = LegalEntity::byUuid($authLegalEntityUUID)->firstOrFail();

        $isFirstLogin = !User::where('uuid', $authUserUUID)->first()?->uuid;

        auth()->shouldUse('ehealth');
        $user = $this->findOrCreateUser($legalEntity, $authUserUUID);

        if (!$user) {
            Log::error(__('auth.login.error.user_authentication', [], 'en'));
            return $this->breakAuth('auth.login.error.user_authentication');
        }

        if ($testUser && ($sessionEmail !== $user->email)) {
            Log::error(__('auth.login.error.test_user_email', [], 'en'));
            return $this->breakAuth('auth.login.error.test_user_email');
        }

        EHealthUserLogin::dispatch($user, $legalEntity, $authUserUUID);

        auth('ehealth')->login($user);

        if ($legalEntity) {
            Log::info(__('auth.login.success.user_auth', [], 'en'), ['User ID' => $user->id]);
            return Redirect::route('dashboard', [$legalEntity])->with('success', $isFirstLogin ? __('auth.login.success.new_user_auth') : null);
        }

        Auth::guard('ehealth')->logout();
        return Redirect::route('login')->with('error', __('auth.login.error.legal_entity.wrong_request'));
    }

    /**
     * Finds an existing user or prepares a new one for the first login.
     * This method NO LONGER performs data synchronization.
     *
     * @param LegalEntity $legalEntity
     * @param string      $authUserUUID
     *
     * @return User|null
     * @throws ApiException
     */
    protected function findOrCreateUser(LegalEntity $legalEntity, string $authUserUUID): ?User
    {
        $user = User::where('uuid', $authUserUUID)->first();
        if ($user) {
            setPermissionsTeamId($legalEntity->id);
            $user->unsetRelation('roles')->unsetRelation('permissions');

            if (!$user->hasAccessToLegalEntityByUuid($legalEntity->uuid)) {
                Log::error(__('auth.login.error.user_authentication', [], 'en') . __(" User {$user->uuid} does not have required access to LegalEntity {$legalEntity->uuid} after sync."));
                return null;
            }

            return $user;
        }

        $userDetailsValidator = $this->validateUserDetailsResponse(EmployeeApi::getUserDetails());
        if ($userDetailsValidator->fails()) {
            Log::error(__('auth.login.error.validation.user_details', [], 'en'), ['errors' => $userDetailsValidator->errors()]);
            return null;
        }

        $userData = $userDetailsValidator->validated();

        if ($userData['id'] !== $authUserUUID) {
            Log::error(__('auth.login.error.user_identity', [], 'en'));
            return null;
        }

        $user = User::where('email', $userData['email'])->first();
        if (!$user) {
            Log::error(__('auth.login.error.user_not_found_by_email', [], 'en') . ": {$userData['email']}");
            return null;
        }

        setPermissionsTeamId($legalEntity->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return $user;
    }

    /**
     * Send request to EHealth to get the token for an auth code,
     * see: https://uaehealthapi.docs.apiary.io/#reference/public.-medical-service-provider-integration-layer/oauth/exchange-oauth-code-grant-to-access-token
     * @return array
     */
    protected function sendEHealthTokenRequest(Request $request, string $selectedLegalEntityUuidFromSession): array
    {
        return EmployeeApi::authenticate(
            $request->input('code'),
            $selectedLegalEntityUuidFromSession,
        );
    }

    /**
     * If any error occurs...
     *
     * @param string $err Text error message via translation
     *
     * @return RedirectResponse
     */
    protected function breakAuth(string $err = ''): RedirectResponse
    {
        $authEhealth = config('ehealth.api.auth_ehealth');

        // Logout user from the system
        if (session()->has($authEhealth) || session()->has(config('ehealth.api.oauth.bearer_token'))) {
            new EHealthRequest('POST', config('ehealth.api.oauth.logout'), [])->sendRequest();

            // Forget bearer token and other token's data
            app(TokenStorage::class)->clear();
        }

        // Forget session data
        session()->forget($authEhealth);

        // Redirect to login page with error message
        $err = $err ? $err : 'auth.login.error.common';

        $logMessage = __($err, [], 'en');

        Log::error($logMessage);

        $errorMessage = __($err);

        return Redirect::to('/login')->with('error', $errorMessage);
    }

    /**
     * Validate EHealth token exchange response
     * see response example: https://uaehealthapi.docs.apiary.io/#reference/public.-medical-service-provider-integration-layer/oauth/exchange-oauth-code-grant-to-access-token?console=1
     * @return ResponseValidator Returned only specified fields
     */
    protected function validateEHealthTokenResponse(array $data): ResponseValidator
    {
        return Validator::make($data, [
            'details' => ['required', 'array'],
            'details.client_id' => ['required','uuid', Rule::exists('legal_entities', 'uuid')],
            'details.scope' => [
                'required',
                function (string $attribute, string $value, Closure $fail) {
                    if ($attribute != 'details.scope') {
                        return;
                    }

                    $scopesReceived = explode(' ', $value);
                    $scopesAvailable = collect(config('ehealth.roles'))
                        ->flatten()
                        ->unique()
                        ->toArray();
                    $diff = array_diff($scopesReceived, $scopesAvailable);

                    if (empty($diff)) {
                        return;
                    }

                    $fail("The following scopes are unsupported: " . implode(', ', $diff) );
                }
            ],
            'details.refresh_token' => ['required','string'],
            'user_id' => ['required', 'uuid'],
            'value' => ['required', 'string'],
            'expires_at' => ['required', 'numeric'],
        ]);
    }

    /**
     * Check authentication $response schema for errors
     *
     *  @return ResponseValidator Returned only specified fields
     */
    protected function validateUserDetailsResponse(array $data): ResponseValidator
    {
        return Validator::make($data, [
            'id' => 'required|string',
            'email' => 'required|string',
            'is_blocked' => 'required|bool',
            'block_reason' => 'nullable|string',
            'person_id' => 'nullable|string',
            'tax_id' => 'nullable|string',
            'settings' => 'nullable|array',
            'inserted_at' => 'required|string',
            'updated_at' => 'required|string',
        ]);
    }
}
