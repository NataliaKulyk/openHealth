@use('App\Enums\Person\AuthenticationMethod')

<div x-data="{
        showAuthMethodModal: $wire.entangle('showAuthMethodModal'),
        authenticationMethods: $wire.entangle('authenticationMethods'),
        selectedMethod: $wire.entangle('form.authorizeWith')
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
                     class="modal-content w-full max-w-4xl mx-auto bg-white dark:bg-slate-900 p-6 rounded-lg"
                >
                    <legend class="legend mb-8 text-2xl font-bold">{{ __('Зміна методу автентифікації через СМС') }}</legend>

                    <div class="bg-red-100 dark:bg-slate-800 rounded-lg p-4 mb-8 flex items-start">
                        @icon('alert-circle', 'w-5 h-5 text-red-700 dark:text-red-300 mr-3 mt-0.5')
                        <p class="text-sm text-red-800 dark:text-red-200">
                            {{ __('patients.if_patient_not_phone_authentication') }}
                        </p>
                    </div>


                    <div class="mt-8 flex gap-3">
                        <button type="button" @click="showAuthMethodModal = false" class="button-minor">
                            {{ __('forms.back') }}
                        </button>

                        <button type="button" @click="showAuthMethodModal = false" class="button-primary-outline">
                            {{ __('patients.to_authentication_methods') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
