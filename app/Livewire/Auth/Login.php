<?php

namespace App\Livewire\Auth;

use Exception;
use App\Models\User;
use Livewire\Component;
use App\Models\LegalEntity;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Session;
use App\Classes\eHealth\Api\EmployeeApi;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use App\Auth\EHealth\Services\TokenStorage;
use Illuminate\Support\Facades\RateLimiter;
use App\Auth\EHealth\Services\EHealthLoginUserHandler;
use Illuminate\Contracts\Validation\Validator as ResponseValidator;

#[Layout('layouts.guest')]
class Login extends Component
{
    /* Amount of the seconds to another login attempt */
    public const int DECAY_SECONDS = 300;

    /* Amount of the wrtong attempt before locking out */
    public const int MAX_LOGIN_ATTEMPTS = 5;

    public string $email = '';

    public string $password = '';

    public bool $isLocalAuth = false;

    public bool $remember = false; // TODO: find out need it or not

    /**
     * Handle an incoming authentication request.
     */
    public function login()
    {
        $key = $this->throttleKey();

        $credentials =  $this->validate();

        /* Check if user doesn't block by attempts exceeding*/
        if (! $this->ensureIsNotRateLimited($credentials))
        {
            /* Number of seconds before login retry */
            $seconds = RateLimiter::availableIn($key);

            return Redirect::route('login')->with('error', __('auth.throttle', [
                'minutes' => ceil($seconds / 60),
                'seconds' => $seconds
            ]));
        }

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->addError('email', __('auth.login.error.validation.auths'));

            return back();
        }

        if ($user && !$this->isLocalAuth && $user->isClientId() ) {
            $url = $this->loginUrl($user);

            return Redirect::to($url);
        }

        if (!Auth::attempt($credentials)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            $this->addError('email', __('auth.login.error.validation.credentials'));

            return back();
        }

        $this->clearLoginAttempts();

        $intended = session('url.intended');

        Session::regenerate();

        /*
         * redirectIntended() takes the URL that Laravel stored in the session as url.intended.
         * However, DevTools or the browser itself often make a request to /.well-known/... before the user clicks anything –
         * and Laravel mistakenly thinks this was the "intended" URL.
         * Therefore, before login, you need to clear url.intended so the browser doesn't land on this service path.
         */
        if (str($intended)->contains('favicon.ico') || str($intended)->contains('.well-known/')) {
            session()->forget('url.intended');
        }

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    protected function rules(): array
    {
        return [
            'email' => 'required|email',
            'password' => $this->isLocalAuth ? 'required|string' : 'nullable',
        ];
    }

    /**
     * Ensure the authentication request is not rate limited
     *
     * @return bool
     */
    protected function ensureIsNotRateLimited(array $credentials): bool
    {
        $key = $this->throttleKey();

        /* Check if already has blocking */
        if (cache()->has("login_lockout:{$key}")) {
            Log::warning(__('auth.login.error.lockout', [], 'en'), [
                'ip' => request()->ip(),
                'email' => $credentials['email']
                ]);

            return false;
        }

        if (! RateLimiter::tooManyAttempts($key, self::MAX_LOGIN_ATTEMPTS)) {
            return true;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($key);

        cache()->put("login_lockout:{$key}", true, now()->addSeconds($seconds));

        $this->addError('email', __('auth.login.error.exceed_login_attempts'));

        return false;
    }

    /**
     * Clear unsuccessfull login attempt data after success login
     *
     * @return void
     */
    protected function clearLoginAttempts(): void
    {
        $key = $this->throttleKey();

        RateLimiter::clear($this->throttleKey());

        cache()->forget("login_lockout:{$key}");
    }

    /**
     * Get the authentication rate limiting throttle key.
     *
     * @return string
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /**
     * This method is called when the user is redirected back from eHealth after it's successfull authentication
     *
     * @return null|RedirectResponse
     */
    public function callback(): ?RedirectResponse
    {
        // exchange code to token
        if (config('ehealth.api.callback_prod') === false) {
            $code = request()->input('code');
            $url =  'http://localhost/ehealth/oauth?code=' . $code;

            return redirect($url);
        }

        if (!request()->has('code')) {
            return Redirect::route('login');
        }

        try {
            $handleLoginUser = app(EHealthLoginUserHandler::class);

            $code = request()->input('code');

            $authResponse = EmployeeApi::authenticate($code);

            $authResponseValidator = $this->validateAuthResponse($authResponse);

            /** @var \Illuminate\Contracts\Validation\Validator $authResponseValidator */
            if ($authResponseValidator->fails()) {
                Log::error(__('auth.login.error.validation.auth', [], 'en'), ['errors' => $authResponseValidator->errors()]);

                return Redirect::route('login')->with('error', __('auth.login.error.validation.auth'));
            }

            $authResponseData = $authResponseValidator->validated();

            app(TokenStorage::class)->store($authResponseData);

            $authUserUUID = $authResponseData['user_id'];
            $authLegalEntityUUID = $authResponseData['details']['client_id'];

            try {
                $legalEntity = LegalEntity::byUuid($authLegalEntityUUID)->firstOrFail();
            } catch (Exception $err) {
                // Error if legal entity cannot be found
                Log::error(__('auth.login.error.unexistent_legal_entity', [], 'en'), ['Error' => $err->getMessage()]);

                return $handleLoginUser->breakAuth('auth.login.error.legal_entity_identity');
            }

            $isFirstLogin = (bool) ! User::where('uuid',$authUserUUID)->first()?->uuid;

            auth()->shouldUse('ehealth');

            $user = $handleLoginUser->checkLoginedUser($legalEntity, $authUserUUID);

            if (!$user) {
                Log::error(__('auth.login.error.user_authentication', [], 'en'));

                return $handleLoginUser->breakAuth('auth.login.error.user_authentication');
            }
        } catch (Exception $err) {
            Log::error(__('auth.login.error.unexpected', [], 'en'), ['Error' => $err->getMessage()]);

            return $handleLoginUser->breakAuth();
        }

        auth('ehealth')->login($user);

        Log::info(__('auth.login.success.user_auth', [], 'en'), ['User ID' => $user->id]);

        return Redirect::route('dashboard')->with('success', $isFirstLogin ? __('auth.login.success.new_user_auth') : null);
    }

     /**
     * Prepare login URL for eHealth depending on the user credentials and redirect URI
     *
     * @param $user
     *
     * @return string
     */
    public static function loginUrl($user): string
    {
        // Base URL and client ID
        $baseUrl = config('ehealth.api.auth_host');
        $redirectUri = config('ehealth.api.redirect_uri');

        // Base query parameters
        $queryParams = [
            'client_id'     => $user->legalEntity->client_id ?? '',
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code'
        ];

        // Additional query parameters if email is provided
        if (!empty($user->email)) {
            $queryParams['email'] = $user->email;
            $queryParams['scope'] = $user->getScopes();
        }

        session()->put(config('ehealth.api.auth_ehealth'), $user->id);

        // Build the full URL with query parameters
        return $baseUrl . '?' . http_build_query($queryParams);
    }

    /**
     * Check authentication $response schema for errors
     *
     * @return ResponseValidator Returned only specified fields
     */
    public function validateAuthResponse(array $data): ResponseValidator
    {
        return Validator::make($data, [
            'details' => 'required|array',
            'details.client_id' => 'required|string',
            'details.scope' => 'required|string',
            'details.refresh_token' => 'required|string',
            'user_id' => 'required|string',
            'value' => 'required|string',
            'expires_at' => 'required|numeric'
        ]);
    }
}
