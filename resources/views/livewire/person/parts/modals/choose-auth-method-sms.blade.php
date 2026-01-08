@use('App\Enums\Person\AuthenticationMethod')

<div x-data="{
        showAuthMethodModal: $wire.entangle('showAuthMethodModal'),
        authenticationMethods: $wire.entangle('authenticationMethods'),
        selectedMethod: $wire.entangle('form.authorizeWith'),
        timer: 60,
        init() {
            setInterval(() => { if(this.timer > 0) this.timer-- }, 1000)
        },
        resetTimer() {
            if(this.timer === 0) {
                this.timer = 60;
            }
        }
    }"
>
    <template x-teleport="body">
        <div x-show="showAuthMethodModal"
             style="display: none"
             @keydown.escape.prevent.stop="showAuthMethodModal = false"
             role="dialog"
             aria-modal="true"
             class="modal"
        >
            <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-transition @click="showAuthMethodModal = false" class="modal-wrapper">
                <div @click.stop
                     x-trap.noscroll.inert="showAuthMethodModal"
                     class="modal-content w-full max-w-4xl mx-auto bg-white dark:bg-slate-900 p-10 rounded-lg shadow-xl"
                >
                    <legend class="legend">{{ __('Автентифікація через СМС') }}</legend>

                    <div class="mt-4 bg-gray-100 dark:bg-slate-800 rounded-lg p-4 mb-8 flex items-start">
                        @icon('alert-circle', 'w-5 h-5 text-slate-600 dark:text-slate-400 mr-3 mt-0.5')
                        <p class="text-sm text-gray-800 dark:text-gray-200">
                            {{ __('patients.please_check_patient_number') }}
                            <span class="font-bold text-slate-900 dark:text-white">+38095123xxxx</span>
                        </p>
                    </div>

                    <legend class="legend">{{ __('patients.code_SMS') }}</legend>


                    <div class="form-row-3 mt-4">
                        <div class="form-group group">
                            <input type="text"
                                   inputmode="numeric"
                                   name="confirmation_code"
                                   id="confirmation_code"
                                   class="peer input"
                                   placeholder=" "
                                   autocomplete="off"/>
                            <label for="confirmation_code" class="label">
                                {{ __('patients.confirmation_code') }}
                            </label>
                        </div>

                        <button type="button"
                                @click="resetTimer()"
                                :disabled="timer > 0"
                                class="button-minor">
                            @icon('mail', 'w-4 h-4 mr-2')
                            <span>{{ __('patients.send_again') }}</span>
                            <template x-if="timer > 0">
                                <span x-text="`(${timer}s)`"></span>
                            </template>
                        </button>
                    </div>

                    <div class="flex gap-4">
                        <button type="button" @click="showAuthMethodModal = false" class="button-minor">
                            {{ __('forms.back') }}
                        </button>

                        <button type="button" @click="showAuthMethodModal = false" class="button-outline-primary">
                            {{ __('patients.to_authentication_methods') }}
                        </button>

                        <button type="button" @click="showAuthMethodModal = false" class="button-primary">
                            {{ __('patients.confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
