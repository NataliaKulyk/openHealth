<legend class="legend">{{ __('patients.authentication_SMS') }}</legend>

<div class="mt-4 bg-gray-100 dark:bg-slate-800 rounded-lg p-4 mb-8 flex items-start">
    @icon('alert-circle', 'w-5 h-5 text-slate-600 dark:text-slate-400 mr-3 mt-0.5')
    <p class="text-sm text-gray-800 dark:text-gray-200">
        {{ __('patients.please_check_patient_number') }}
        <span class="font-bold text-slate-900 dark:text-white">{{ $phoneNumber }}</span>
    </p>
</div>

<legend class="legend">{{ __('patients.code_SMS') }}</legend>

<div class="form-row-3 mt-4">
    <div class="form-group group">
        <input type="text"
               wire:model="code"
               inputmode="numeric"
               name="code"
               id="code"
               class="peer input"
               placeholder=" "
               autocomplete="off"
        />
        <label for="code" class="label">
            {{ __('patients.confirmation_code') }}
        </label>
    </div>
</div>

<div class="flex gap-4">
    <button type="button" wire:click="setStep(1)" class="button-minor">
        {{ __('forms.back') }}
    </button>

    <button type="button" wire:click="setStep(0)" class="button-outline-primary">
        {{ __('patients.to_authentication_methods') }}
    </button>

    <button type="button" wire:click="completeVerifyingOwnership" class="button-primary">
        {{ __('patients.confirm') }}
    </button>
</div>
