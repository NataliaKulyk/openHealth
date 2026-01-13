<legend class="legend mb-8 text-2xl font-bold">{{ __('patients.enter_new_phone') }}</legend>

<div class="form-row-3">
    <div class="form-group">
        <input type="tel"
               placeholder=" "
               class="peer input"
               wire:model="newPhoneNumber"
               x-mask="+380999999999"
        />
        <label class="label">{{ __('forms.phone') }}</label>
    </div>
</div>

<div class="mt-8 flex gap-3">
    <button type="button" wire:click="setStep(1)" class="button-minor">
        {{ __('forms.back') }}
    </button>

    <button type="button" wire:click="updatePhoneNumber" class="button-primary">
        {{ __('patients.confirm') }}
    </button>
</div>
