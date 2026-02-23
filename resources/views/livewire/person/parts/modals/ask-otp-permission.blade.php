@use('App\Enums\Person\AuthStep')

<div>
    <legend class="legend">{{ __('patients.authentication_SMS') }}</legend>

    <div class="mt-4 bg-gray-100 dark:bg-slate-800 rounded-lg p-4 mb-8 flex items-start">
        @icon('alert-circle', 'w-5 h-5 text-slate-600 dark:text-slate-400 mr-3 mt-0.5')
        <p class="text-sm text-gray-800 dark:text-gray-200">
            {{ __('patients.please_check_patient_number') }}
            <span class="font-bold text-slate-900 dark:text-white">{{ $phoneNumber }}</span>
        </p>
    </div>

    <div class="flex gap-4">
        <button type="button" @click="localStep = {{ AuthStep::CHANGE_PHONE_INITIAL }}" class="button-minor">
            {{ __('forms.back') }}
        </button>

        <button type="button" @click="localStep = {{ AuthStep::INITIAL }}" class="button-outline-primary">
            {{ __('patients.to_authentication_methods') }}
        </button>

        <button type="button" wire:click="update" class="button-primary">
            {{ __('forms.confirm') }}
        </button>
    </div>
</div>
