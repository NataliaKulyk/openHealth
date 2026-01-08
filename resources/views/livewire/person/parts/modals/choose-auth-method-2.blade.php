@use('App\Enums\Person\AuthenticationMethod')

<div x-data="{
        timer: 60,
        init() {
            setInterval(() => { if(this.timer > 0) this.timer-- }, 1000)
        },
        resetTimer() {
            if(this.timer === 0) {
                this.timer = 60;
                $wire.sendNewSms();
            }
        }
    }"
>
    <legend class="legend">{{ __('patients.authentication_SMS') }}</legend>

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
                   wire:model.defer="confirmation_code"
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
        <button type="button" wire:click="setStep(1)" class="button-minor">
            {{ __('forms.back') }}
        </button>

        <button type="button" wire:click="setStep(0)" class="button-outline-primary">
            {{ __('patients.to_authentication_methods') }}
        </button>

        <button type="button" wire:click="setStep(3)" class="button-primary">
            {{ __('patients.confirm') }}
        </button>
    </div>
</div>
