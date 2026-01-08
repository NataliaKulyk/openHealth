<legend class="legend mb-8 text-2xl font-bold">{{ __('patients.changing_SMS_method') }}</legend>

<div class="bg-gray-100 dark:bg-slate-800 rounded-lg p-4 mb-8 flex items-start">
    @icon('alert-circle', 'w-5 h-5 text-gray-700 dark:text-gray-300 mr-3 mt-0.5')
    <p class="text-sm text-gray-800 dark:text-gray-200">
        {{ __('patients.please_clarify_phone_number') }}
        <span class="font-bold">+38095123xxxx</span>
    </p>
</div>

<div class="mt-8 flex gap-3">
    <button type="button" wire:click="setStep(0)" class="button-minor">
        {{ __('patients.back_authentication_methods') }}
    </button>

    <button type="button" wire:click="setStep(3)" class="button-primary-outline-red">
        {{ __('patients.no_access') }}
    </button>

    <button type="button" wire:click="setStep(2)" class="button-primary">
        {{ __('patients.available_access') }}
    </button>
</div>
