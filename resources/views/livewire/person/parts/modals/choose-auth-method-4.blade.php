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
                    <legend class="legend mb-8 text-2xl font-bold">{{ __('patients.enter_new_phone') }}</legend>

                    <div class="form-row-4">
                        <div class="form-group">
                            <input
                                type="tel"
                                placeholder=" "
                                class="peer input"
                                x-model="phone.number"
                                x-mask="+380999999999"
                            />
                            <label class="label">{{ __('forms.phone') }}</label>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button type="button" @click="showAuthMethodModal = false" class="button-minor">
                            {{ __('forms.back') }}
                        </button>

                        <button type="button" @click="showAuthMethodModal = false" class="button-primary">
                            {{ __('patients.confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
