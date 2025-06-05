@php
    $hasEmailError = $errors->has('email');
    $hasPasswordError = $errors->has('password');
@endphp

<div class="fragment">
    <x-authentication-card>

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('auth.login.enter') }}
        </h2>

        <form
            x-data="{ isLocalAuth: $wire.entangle('isLocalAuth')  }"
            autocomplete="off"
            wire:submit.prevent="login"
        >
            <div class="form-group group">
                <input
                    required
                    type="email"
                    placeholder=" "
                    id="email"
                    autocomplete="off"
                    wire:model="email"
                    aria-describedby="{{ $hasEmailError ? 'hasEmailErrorHelp' : '' }}"
                    class="input {{ $hasEmailError  ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                />

                @if($hasEmailError)
                    <p id="hasEmailErrorHelp" class="text-error">
                        {{ $errors->first('email') }}
                    </p>
                @endif

                <label for="email" class="label z-10">
                    {{ __('Email') }}
                </label>
            </div>

            <div class="mt-6" x-show="isLocalAuth" x-cloak>
                <div class="form-group group">
                    <input
                        :required="isLocalAuth"
                        type="password"
                        placeholder=" "
                        autocomplete="off"
                        id="password"
                        wire:model="password"
                        aria-describedby="{{ $hasPasswordError ? 'hasPasswordErrorHelp' : '' }}"
                        class="input {{ $hasPasswordError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                    />

                    @if($hasPasswordError)
                        <p id="hasPasswordErrorHelp" class="text-error">
                            {{ $errors->first('password') }}
                        </p>
                    @endif

                    <label for="password" class="label z-10">
                        {{ __('auth.login.password') }}
                    </label>
                </div>
            </div>

            <div class="block mt-4">
                <div class="form-group group">
                    <input
                        type="checkbox"
                        id="is_local_auth"
                        class="default-checkbox text-blue-500 focus:ring-blue-300"
                        x-model="isLocalAuth"
                        :checked="isLocalAuth"
                    >

                    <label
                        for="is_local_auth"
                        class="ms-2 text-xs font-medium text-gray-500 dark:text-gray-300"
                    >
                        {{ __('auth.login.no_ehealth_login') }}
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end mt-4">
                <button
                    type="submit"
                    id="submitButton"
                    class="login-button cursor-pointer"
                >
                    {{ __('auth.login.enter')  }}
                </button>
            </div>

            <div class="mt-6 text-center">
                <p class="text-[0.8125rem] font-medium text-gray-400 dark:text-gray-400">
                    <a
                        wire:navigate
                        class="hover:text-gray-700 text-gray-400 dark:text-gray-400"
                        href="{{ route('register') }}"
                    >
                        {{ __('auth.login.register') }} /
                    </a>

                    @if (Route::has('password.request'))
                        <a
                            wire:navigate
                            class="hover:text-gray-700 text-gray-400 dark:text-gray-400"
                            href="{{ route('password.request') }}"
                        >
                            {{ __('auth.login.forgot_password') }}
                        </a>
                    @endif
                </p>
            </div>

        </form>
    </x-authentication-card>
</div>
