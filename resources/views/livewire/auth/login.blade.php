@php
    $hasEmailError = $errors->has('email');
    $hasPasswordError = $errors->has('password');
@endphp

<div class="fragment">
    <x-authentication-card>

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('forms.enter') }}
        </h2>

        <form wire:submit.prevent="login" x-data="{ isLocalAuth: $wire.entangle('isLocalAuth') }">
            <div class="form-group group">
                <input wire:model="email"
                       required
                       type="email"
                       placeholder=" "
                       id="email"
                       autocomplete="off"
                       name="email"
                       aria-describedby="{{ $hasEmailError ? 'hasEmailErrorHelp' : '' }}"
                       class="input {{ $hasEmailError  ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                />

                @if($hasEmailError)
                    <p id="hasEmailErrorHelp" class="text-error">
                        {{ $errors->first('email') }}
                    </p>
                @endif

                <label for="email" class="label z-10">
                    {{ __('forms.email') }}
                </label>
            </div>

            {{-- Legal Entity Select --}}
            <x-forms.combobox :options="$legalEntitiesList"
                              x-show="!isLocalAuth"
                              x-cloak
                              x-transition:enter="transition ease-out duration-300"
                              x-transition:enter-start="opacity-0 scale-95"
                              x-transition:enter-end="opacity-100 scale-100"
                              is-required="!isLocalAuth"
                              bind="legalEntityUUID"
                              bindValue='uuid'
                              bindParam='name'
                              class="!z-[100] mt-6"
            />

            {{-- Role select --}}
            @if($showRoleSelect && !$isLocalAuth)
                <div class="form-group group">
                    <select wire:model="role" class="input-select peer">
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach($rolesList as $role)
                            <option value="{{ $role }}">{{ __("auth.login.role.$role") }}</option>
                        @endforeach
                    </select>

                    @error('role')<p class="text-error">{{ $message }}</p>@enderror
                </div>
            @endif

            <div class="mt-6"
                 x-show="isLocalAuth"
                 x-cloak
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
            >
                <div class="form-group group">
                    <input wire:model="password"
                           :required="isLocalAuth"
                           type="password"
                           placeholder=" "
                           autocomplete="off"
                           id="password"
                           aria-describedby="{{ $hasPasswordError ? 'hasPasswordErrorHelp' : '' }}"
                           class="input {{ $hasPasswordError ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                    />

                    @if($hasPasswordError)
                        <p id="hasPasswordErrorHelp" class="text-error">
                            {{ $errors->first('password') }}
                        </p>
                    @endif

                    <label for="password" class="label z-10">
                        {{ __('forms.password') }}
                    </label>
                </div>
            </div>

            <div class="block mt-4">
                <div class="form-group group">
                    <input x-model="isLocalAuth"
                           type="checkbox"
                           id="is_local_auth"
                           class="default-checkbox text-blue-500 focus:ring-blue-300"
                           :checked="isLocalAuth"
                    >

                    <label for="is_local_auth" class="ms-2 text-xs font-medium text-gray-500 dark:text-gray-300">
                        {{ __('auth.login.no_ehealth_login') }}
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end mt-4">
                <button type="submit"
                        id="submitButton"
                        class="login-button cursor-pointer"
                >
                    {{ __('forms.enter') }}
                </button>
            </div>

            <div class="mt-6 text-center">
                <p class="text-[0.8125rem] font-medium text-gray-400 dark:text-gray-400">
                    <a href="{{ route('register') }}"
                       wire:navigate
                       class="hover:text-gray-700 text-gray-400 dark:text-gray-400"
                    >
                        {{ __('forms.need_register') }} /
                    </a>

                    @if (Route::has('forgot.password'))
                        <a href="{{ route('forgot.password') }}"
                           wire:navigate
                           class="hover:text-gray-700 text-gray-400 dark:text-gray-400"
                        >
                            {{ __('forms.forgot_password') }}
                        </a>
                    @endif
                </p>
            </div>
        </form>
    </x-authentication-card>

    <x-forms.loading/>
</div>
