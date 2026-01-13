<div x-data="{
        timer: 60,
        init() {
            setInterval(() => { if(this.timer > 0) this.timer-- }, 1000)
        },
        resetTimer() {
            if(this.timer === 0) {
                this.timer = 60;
                $wire.resendCode();
            }
        }
    }"
>
    <legend class="legend mt-6">{{ __('patients.confirmation_code') }}</legend>

    <div class="form-row-3 mt-4">
        <div class="form-group group">
            <input type="text"
                   wire:model="verificationCode"
                   inputmode="numeric"
                   name="verificationCode"
                   id="verificationCode"
                   class="peer input"
                   placeholder=" "
                   autocomplete="off"
            />
            <label for="verificationCode" class="label">
                {{ __('patients.code_SMS') }}
            </label>
        </div>

        <button type="button"
                @click="resetTimer()"
                :disabled="timer > 0"
                class="button-minor"
        >
            @icon('mail', 'w-4 h-4 mr-2')
            <span>{{ __('patients.send_again') }}</span>
            <template x-if="timer > 0">
                <span x-text="`(${timer}c)`"></span>
            </template>
        </button>
    </div>

    <div class="flex gap-4">
        <button type="button" wire:click="setStep(4)" class="button-minor">
            {{ __('forms.back') }}
        </button>

        <button type="button" wire:click="setStep(0)" class="button-outline-primary">
            {{ __('patients.to_authentication_methods') }}
        </button>

        <button type="button" wire:click="approveUpdatingPhoneNumber" class="button-primary">
            {{ __('patients.confirm') }}
        </button>
    </div>
</div>
