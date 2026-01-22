@use('App\Enums\Person\AuthenticationMethod')

<fieldset class="fieldset"
          x-data="{
              authenticationMethods: $wire.entangle('form.person.authenticationMethods'),
              isIncapacitated: $wire.entangle('isIncapacitated'),
              showAuthDocDrawer: false,
              smsCode: '',
              infoConfirmed: false,

              get availableAuthMethods() {
                  return [
                      {
                          value: '{{ AuthenticationMethod::OTP->value }}',
                          label: '{{ __('forms.authentication') }} {{ AuthenticationMethod::OTP->label() }}'
                      },
                      {
                          value: '{{ AuthenticationMethod::OFFLINE->value }}',
                          label: '{{ __('forms.authentication') }} {{ AuthenticationMethod::OFFLINE->label() }}'
                      },
                      {
                          value: '{{ AuthenticationMethod::THIRD_PERSON->value }}',
                          label: '{{ __('forms.authentication') }} {{ AuthenticationMethod::THIRD_PERSON->label() }}'
                      }
                  ];
              }
          }"
>
    <legend class="legend">{{ __('forms.authentication') }}</legend>

    <div class="form-row-3">
        <div class="form-group group">
            <label for="relationType" class="sr-only">
                {{ __('forms.authentication') }}
            </label>
            <select x-model="authenticationMethods[0].type"
                    x-init="$nextTick(() => authenticationMethods = JSON.parse(JSON.stringify(authenticationMethods)))"
                    id="relationType"
                    class="input-select peer @error('form.person.authenticationMethods.*.type') input-error @enderror"
                    required
            >
                <option selected value="">
                    {{ __('forms.select') }} {{ mb_strtolower(__('forms.authentication')) }} *
                </option>
                <template x-for="method in availableAuthMethods" :key="method.value">
                    <option :value="method.value" x-text="method.label"></option>
                </template>
            </select>

            @error('form.person.authenticationMethods.*.type') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <template x-if="authenticationMethods[0]?.type === '{{ AuthenticationMethod::OTP->value }}'">
        <div class="form-row-3">
            <div class="form-group group">
                <input x-model="authenticationMethods[0].phoneNumber"
                       type="text"
                       x-mask="+380999999999"
                       name="phoneNumber"
                       id="phoneNumber"
                       class="input peer @error('form.person.authenticationMethods.*.phoneNumber') input-error @enderror"
                       placeholder=" "
                       required
                       autocomplete="off"
                />
                <label for="phoneNumber" class="label">
                    {{ __('forms.phone_number') }}
                </label>

                @error('form.person.authenticationMethods.*.phoneNumber')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </template>

    <template x-if="authenticationMethods[0]?.type === '{{ AuthenticationMethod::THIRD_PERSON->value }}'">
        <div class="mt-4">
            <button type="button"
                    @click="showAuthDocDrawer = true"
                    class="item-add"
            >
                {{ __('patients.add_authentication_documents') }}
            </button>
        </div>
    </template>

    {{-- Third Person Authentication Drawer --}}
    <div x-show="showAuthDocDrawer"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         x-cloak
         @click="showAuthDocDrawer = false"
         class="fixed inset-0 bg-black/25 z-30"
    ></div>

    <div x-show="showAuthDocDrawer"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         x-cloak
         class="fixed top-0 right-0 z-40 h-screen pt-20 p-4 overflow-y-auto transition-transform bg-white w-4/5 dark:bg-gray-800 shadow-2xl"
         id="auth-documents-drawer"
         tabindex="-1"
    >
        <div class="p-6">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('patients.auth_through_another_person') }}
            </h3>

            {{-- Medical Worker Confirmation --}}
            <div class="p-4 mb-6 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-0.5">
                        @icon('alert-circle', 'w-5 h-5 text-gray-500 dark:text-gray-400 mr-3')
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-800 dark:text-white mb-2">
                            {{ __('patients.medical_worker_confirmation') }}
                        </p>
                        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <p>- {{ __('patients.confirm_identity') }}</p>
                            <p>- {{ __('patients.confirm_legal_representative') }}</p>
                            <p>- {{ __('patients.confirm_verification') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Patient Memo --}}
            <div class="p-4 mb-4 bg-gray-100 dark:bg-gray-700 rounded-lg">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mt-0.5">
                        @icon('alert-circle', 'w-5 h-5 text-gray-500 dark:text-gray-400 mr-3')
                    </div>
                    <div class="flex-1">
                        <p class="font-bold text-gray-800 dark:text-white mb-2">
                            {{ __('patients.leaflet') }}
                        </p>
                        <div class="space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <p>{{ __('patients.leaflet_intro') }}</p>
                            <p>- {{ __('patients.leaflet_point_1') }}</p>
                            <p>- {{ __('patients.leaflet_point_2') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" class="flex items-center gap-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-sm mb-6">
                @icon('printer', 'w-4 h-4')
                {{ __('patients.print_leaflet') }}
            </button>

            {{-- Information Confirmed Checkbox --}}
            <div class="flex items-center gap-2 mb-6">
                <x-checkbox class="default-checkbox"
                            x-model="infoConfirmed"
                            id="infoConfirmed"
                />
                <label for="infoConfirmed" class="text-sm text-gray-900 dark:text-white">
                    {{ __('patients.informed') }}
                </label>
            </div>

            {{-- SMS Code Section --}}
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ __('patients.code_sms') }}
                </h4>
                <div class="form-row-3 items-end">
                    <div class="form-group group">
                        <input x-model="smsCode"
                               type="text"
                               name="smsCode"
                               id="smsCode"
                               class="input peer"
                               placeholder=" "
                               autocomplete="off"
                        />
                        <label for="smsCode" class="label">
                            {{ __('patients.confirmation_code_sms') }}
                        </label>
                    </div>
                    <button type="button" class="button-minor flex items-center gap-2 whitespace-nowrap">
                        @icon('mail', 'w-5 h-5 flex-shrink-0 text-gray-700 dark:text-gray-300')
                        {{ __('patients.resend_code') }}
                    </button>
                </div>
                <button type="button" class="button-primary mt-4">
                    {{ __('forms.confirm') }}
                </button>
            </div>

            {{-- File Uploads --}}
            <div class="space-y-6 mb-6">
                <div x-data="{ fileName: '' }">
                    <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('forms.birth_certificate_scans') }} <span class="text-red-500">*</span>
                    </label>
                    <div class="file-input-wrapper">
                        <label for="authBirthCertScans" class="file-input-button">
                            {{ __('patients.select_file') }}
                        </label>
                        <span class="file-input-text" x-text="fileName || '{{ __('patients.file_not_selected') }}'"></span>
                        <input type="file"
                               class="hidden"
                               id="authBirthCertScans"
                               accept=".jpeg,.jpg"
                               multiple
                               @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' {{ __('forms.files_selected') }}' : ($event.target.files[0]?.name || '')"
                        />
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('forms.max_file_size_and_format') }}
                    </p>
                </div>

                <div x-data="{ fileName: '' }">
                    <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('forms.apostille_scans') }}
                    </label>
                    <div class="file-input-wrapper">
                        <label for="authApostilleScans" class="file-input-button">
                            {{ __('patients.select_file') }}
                        </label>
                        <span class="file-input-text" x-text="fileName || '{{ __('patients.file_not_selected') }}'"></span>
                        <input type="file"
                               class="hidden"
                               id="authApostilleScans"
                               accept=".jpeg,.jpg"
                               multiple
                               @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' {{ __('forms.files_selected') }}' : ($event.target.files[0]?.name || '')"
                        />
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('forms.max_file_size_and_format') }}
                    </p>
                </div>

                <div x-data="{ fileName: '' }">
                    <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white">
                        {{ __('forms.translation_scans') }}
                    </label>
                    <div class="file-input-wrapper">
                        <label for="authTranslationScans" class="file-input-button">
                            {{ __('patients.select_file') }}
                        </label>
                        <span class="file-input-text" x-text="fileName || '{{ __('patients.file_not_selected') }}'"></span>
                        <input type="file"
                               class="hidden"
                               id="authTranslationScans"
                               accept=".jpeg,.jpg"
                               multiple
                               @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' {{ __('forms.files_selected') }}' : ($event.target.files[0]?.name || '')"
                        />
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ __('forms.max_file_size_and_format') }}
                    </p>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex gap-3">
                <button type="button"
                        class="button-minor"
                        @click="showAuthDocDrawer = false"
                >
                    {{ __('forms.cancel') }}
                </button>
                <button type="button" class="button-primary flex items-center gap-2">
                    {{ __('patients.send_files') }}
                    @icon('arrow-right', 'w-4 h-4')
                </button>
            </div>
        </div>
    </div>
</fieldset>
