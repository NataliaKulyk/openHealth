{{-- Drawer for SMS authentication --}}
<div x-show="Alpine.store('authDrawer').showAuthSmsDrawer"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     x-cloak
     class="fixed top-0 right-0 z-40 h-screen pt-20 p-4 overflow-y-auto transition-transform bg-white w-4/5 dark:bg-gray-800 shadow-2xl"
     id="auth-sms-drawer"
     tabindex="-1"
>
    <h3 class="modal-header">
        {{ __('patients.authentication_SMS') }}
    </h3>

    <div class="mt-4">
        {{-- Information message --}}
        <div class="bg-gray-100 rounded-lg p-4 mb-6">
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0 mt-0.5">
                    @icon('alert-circle', 'w-5 h-5 text-gray-600 dark:text-gray-400')
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-700 dark:text-gray-300">
                        {{ __('patients.please_check_patient_number') }}
                    </p>
                </div>
            </div>
        </div>

        <div x-data="{
            timer: 60,
            verificationCode: '',
            timerInterval: null,
            init() {
                this.startTimer();
                this.$watch(() => Alpine.store('authDrawer').showAuthSmsDrawer, (value) => {
                    if(value) {
                        this.startTimer();
                    }
                });
            },
            startTimer() {
                this.timer = 60;
                if(this.timerInterval) clearInterval(this.timerInterval);
                this.timerInterval = setInterval(() => { 
                    if(this.timer > 0) {
                        this.timer--;
                    } else {
                        clearInterval(this.timerInterval);
                    }
                }, 1000);
            },
            resetTimer() {
                if(this.timer === 0) {
                    this.startTimer();
                }
            }
        }">
            <legend class="legend mt-6">{{ __('forms.confirmation_code_from_SMS') }}</legend>

            <div class="form-row-3 mt-4">
                <div class="form-group group">
                    <input type="text"
                           x-model="verificationCode"
                           inputmode="numeric"
                           name="verificationCode"
                           id="verificationCode"
                           class="peer input"
                           placeholder=" "
                           autocomplete="off"
                    />
                    <label for="verificationCode" class="label">
                        {{ __('patients.code_sms') }}
                    </label>
                </div>

                <button type="button"
                        @click="resetTimer()"
                        :disabled="timer > 0"
                        class="button-minor"
                >
                    @icon('mail', 'w-4 h-4 mr-2')
                    <span>{{ __('forms.send_again') }}</span>
                    <template x-if="timer > 0">
                        <span x-text="`(через ${timer} c)`"></span>
                    </template>
                </button>
            </div>

            <div class="flex gap-4 mt-8">
                <button type="button"
                        @click="Alpine.store('authDrawer').showAuthSmsDrawer = false"
                        class="button-minor">
                    {{ __('forms.back') }}
                </button>

                <button type="button"
                        @click="Alpine.store('authDrawer').showSignatureDrawer = true"
                        class="button-outline-primary">
                    {{ __('patients.to_authentication_methods') }}
                </button>

                <button type="button"
                        @click="Alpine.store('authDrawer').showAuthSmsDrawer = false"
                        class="button-primary">
                    {{ __('forms.confirm') }}
                </button>
            </div>
        </div>
    </div>
</div>
