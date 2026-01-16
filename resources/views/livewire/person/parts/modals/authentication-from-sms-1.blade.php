<div wire:key="auth-step-add-sms">
    <legend class="legend">
        {{ __('patients.adding_authentication_method_SMS') }}
    </legend>

    <div class="space-y-10">
        <div class="form-row-3">
            <div class="form-group group">
                <input type="tel"
                       placeholder=" "
                       class="peer input !py-2"
                       wire:model="form.phoneNumber"
                       id="add_sms_phone"
                       x-mask="+380999999999"
                />
                <label class="label" for="add_sms_phone">{{ __('+380') }}</label>
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group group">
                <input type="text"
                       placeholder=" "
                       class="peer input !py-2"
                       wire:model="form.methodName"
                       id="add_sms_name"
                />
                <label class="label" for="add_sms_name">{{ __('Назва методу автентифікації') }}</label>
            </div>
        </div>
    </div>

    <div class="mt-12 flex gap-4">
        <button type="button" wire:click="setStep(0)" class="button-minor">
            {{ __('forms.back') }}
        </button>

        <button type="button" wire:click="submitSmsMethod" class="button-primary">
            {{ __('patients.confirm') }}
        </button>
    </div>
</div>
