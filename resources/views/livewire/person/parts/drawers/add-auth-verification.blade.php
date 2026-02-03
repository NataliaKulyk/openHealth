{{-- Auth Drawer Overlay --}}
<div x-show="showAuthDrawer"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     x-cloak
     @click="showAuthDrawer = false"
     class="fixed inset-0 bg-gray-900/50"
     style="z-index: 65;"
></div>

{{-- Auth Drawer --}}
<div x-show="showAuthDrawer"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full"
     x-cloak
     class="fixed top-0 right-0 h-screen pt-16 bg-white dark:bg-gray-800 shadow-2xl"
     style="z-index: 70; width: calc(80% - 65px);"
     id="auth-drawer"
     tabindex="-1"
     x-data="{
         localTimer: 60,
         timerInterval: null,
         startTimer() {
             this.localTimer = 60;
             if(this.timerInterval) clearInterval(this.timerInterval);
             this.timerInterval = setInterval(() => { if(this.localTimer > 0) this.localTimer-- }, 1000)
         },
         resetTimer() {
             if(this.localTimer === 0) {
                 this.localTimer = 60;
                 this.startTimer();
             }
         }
     }"
     x-init="$watch('showAuthDrawer', value => { if(value) startTimer() })"
>
    <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-4">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
            {{ __('patients.authentication_SMS') }}
        </h2>
    </div>

    <div class="overflow-y-auto p-6 bg-white dark:bg-gray-800" style="height: calc(100% - 70px);">
        <div class="mt-4 bg-gray-100 dark:bg-slate-800 rounded-lg p-4 mb-8 flex items-start">
            @icon('alert-circle', 'w-5 h-5 text-slate-600 dark:text-slate-400 mr-3 mt-0.5')
            <p class="text-sm text-gray-800 dark:text-gray-200">
                {{ __('patients.please_check_patient_number') }}
                <span class="font-bold text-slate-900 dark:text-white">+380931823****</span>
            </p>
        </div>

        <legend class="legend">{{ __('patients.code_sms') }}</legend>

        <div class="form-row-3">
            <div class="form-group group">
                <input type="text"
                       x-model="verificationCode"
                       inputmode="numeric"
                       name="authVerificationCode"
                       id="authVerificationCode"
                       class="peer input"
                       placeholder=" "
                       autocomplete="off"
                />
                <label for="authVerificationCode" class="label">
                    {{ __('forms.confirmation_code_from_SMS') }}
                </label>
            </div>

            <button type="button"
                    @click="resetTimer()"
                    :disabled="localTimer > 0"
                    class="button-minor flex items-end gap-4 mt-4 mb-8 flex-1 max-w-xs"
            >
                @icon('mail', 'w-4 h-4')
                <span>{{ __('forms.send_again') }}</span>
                <template x-if="localTimer > 0">
                    <span x-text="`(через ${localTimer} c)`"></span>
                </template>
            </button>
        </div>

        <div class="flex gap-3">
            <button type="button"
                    @click="showAuthDrawer = false"
                    class="button-minor"
            >
                {{ __('forms.back') }}
            </button>

            <button type="button"
                    @click="showAuthDrawer = false; showDocumentDrawer = false; showLegalRepDrawer = false"
                    class="button-outline-primary"
            >
                {{ __('patients.to_authentication_methods') }}
            </button>

            <button type="button"
                    @click="showSignatureDrawer = true"
                    class="button-primary"
            >
                {{ __('forms.confirm') }}
            </button>
        </div>
    </div>
</div>

@include('livewire.person.parts.drawers.add-signature')
