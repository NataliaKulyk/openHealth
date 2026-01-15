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
                     class="modal-content w-full max-w-4xl mx-auto"
                >
                    @if($authStep === 0)
                        <div wire:key="auth-step-0">
                            <legend class="legend mb-8">{{ __('patients.authentication_methods') }}</legend>

                            <template x-if="!authenticationMethods || authenticationMethods.length === 0">
                                <div class="bg-red-100 rounded-lg mb-8">
                                    <div class="p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            @icon('alert-circle', 'w-5 h-5 text-red-700')
                                            <p class="font-semibold text-red-700">{{ __('forms.patient_has_no_auth_methods') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <template x-if="authenticationMethods && authenticationMethods.length > 0">
                                <div class="space-y-4">
                                    <template x-for="method in authenticationMethods" :key="method.uuid">
                                        <div class="fieldset border dark:border-white p-3 rounded space-y-3">
                                            <div class="flex items-start justify-between">
                                                <div class="shrink"
                                                     x-data="{
                                                         labels: @js(AuthenticationMethod::options()),
                                                         prefix: '{{ __('forms.authentication') }}'
                                                     }"
                                                >
                                                    <h3 class="text-gray-900 dark:text-white font-bold"
                                                        x-text="`${prefix} ${labels[method.type] ?? method.type}`"
                                                    ></h3>

                                                    <p class="default-p"
                                                       x-text="'{{ __('patients.method_name') }}: ' + (method.alias || '—')"
                                                    ></p>
                                                </div>

                                                <div class="flex items-center gap-4">
                                                    <div x-data="{ open: false }" class="relative">
                                                        <button @click="open = !open" type="button"
                                                                class="text-blue-600 hover:underline text-sm whitespace-nowrap"
                                                        >
                                                            {{ __('patients.change') }}
                                                        </button>

                                                        <div x-show="open"
                                                             @click.away="open = false"
                                                             style="display: none"
                                                             class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl z-50 p-2 border border-gray-100"
                                                        >
                                                            <template x-if="method.type === '{{ AuthenticationMethod::OTP->value }}'">
                                                                <button @click="open = false"
                                                                        wire:click.prevent="selectAuthMethod(method.uuid, method.type, 1)"
                                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded text-gray-700"
                                                                >
                                                                    {{ __('patients.change_phone_number') }}
                                                                </button>
                                                            </template>

                                                            <template x-if="method.type === '{{ AuthenticationMethod::OFFLINE->value }}'">
                                                                <button wire:click.prevent="selectAuthMethod(method.uuid, method.type, 1)"
                                                                        @click="open = false"
                                                                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded text-gray-700"
                                                                >
                                                                    {{ __('patients.change_method_to_sms') }}
                                                                </button>
                                                            </template>

                                                            <button @click="open = false"
                                                                    wire:click.prevent="selectAuthMethod(method.uuid, method.type, 7)"
                                                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded text-gray-700"
                                                            >
                                                                {{ __('patients.change_method_alias') }}
                                                            </button>

                                                            <button wire:click.prevent="selectAuthMethod(method.uuid, method.type, 3)"
                                                                    @click="open = false"
                                                                    class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded text-gray-700"
                                                            >
                                                                {{ __('patients.deactivate_method') }}
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <button wire:click="update"
                                                            class="button-primary whitespace-nowrap"
                                                            @click="selectedMethod = method.id || method.uuid; showAuthMethodModal = false"
                                                    >
                                                        {{ __('forms.select') }}
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="space-y-2">
                                                <template x-if="method.type === '{{ AuthenticationMethod::OTP->value }}'">
                                                    <div class="space-y-4">
                                                        <label for="phoneNumber" class="label-modal">
                                                            {{ __('forms.phone_number') }}
                                                        </label>
                                                        <div class="form-row-3">
                                                            <input type="tel"
                                                                   class="input-modal"
                                                                   :value="method.phoneNumber"
                                                                   id="phoneNumber"
                                                                   readonly
                                                            >
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="method.type === '{{ AuthenticationMethod::THIRD_PERSON->value }}'">
                                                    <div class="space-y-4">
                                                        <div class="form-row-2">
                                                            <div class="form-group">
                                                                <label for="alias" class="label-modal">
                                                                    {{ __('patients.alias') }}
                                                                </label>
                                                                <input type="text"
                                                                       :value="method.alias"
                                                                       class="input-modal"
                                                                       name="alias"
                                                                       readonly
                                                                >
                                                            </div>

                                                            <div class="form-group"
                                                                 x-data="{ endedAt: method.ehealthEndedAt || method.endedAt }"
                                                            >
                                                                @icon('calendar-month', 'w-5 h-5 svg-input absolute left-1 !top-2/3 transform -translate-y-1/2 pointer-events-none')

                                                                <label for="endedAt" class="label-modal">
                                                                    {{ __('patients.ended_at') }}
                                                                    <span class="text-red-600"></span>
                                                                </label>
                                                                <input x-model="endedAt"
                                                                       x-init="$nextTick(() => { if (endedAt) $el.value = endedAt })"
                                                                       datepicker-max-date="{{ now()->format('d.m.Y') }}"
                                                                       datepicker-format="dd.mm.yyyy"
                                                                       type="text"
                                                                       name="endedAt"
                                                                       id="endedAt"
                                                                       class="input-modal datepicker-input"
                                                                       autocomplete="off"
                                                                       readonly
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="form-row-2">
                                                            <div class="form-group">
                                                                <label for="confidantPersonFullName"
                                                                       class="label-modal"
                                                                >
                                                                    {{ __('patients.confidant_full_name') }}
                                                                </label>
                                                                <input type="text"
                                                                       :value="method.confidantPerson.name"
                                                                       class="input-modal"
                                                                       id="confidantPersonFullName"
                                                                       name="confidantPersonFullName"
                                                                       readonly
                                                                >
                                                            </div>

                                                            <div class="form-group">
                                                                <label for="taxId" class="label-modal">
                                                                    {{ __('forms.rnokpp') }}
                                                                    <span class="text-red-600"></span>
                                                                </label>
                                                                <input :value="method.confidantPerson.taxId"
                                                                       type="text"
                                                                       name="taxId"
                                                                       id="taxId"
                                                                       class="input-modal"
                                                                       autocomplete="off"
                                                                       readonly
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="form-row-2">
                                                            <div class="form-group">
                                                                <label for="unzr" class="label-modal">
                                                                    {{ __('patients.unzr') }}
                                                                </label>
                                                                <input type="text"
                                                                       :value="method.confidantPerson.unzr"
                                                                       class="input-modal"
                                                                       name="unzr"
                                                                       readonly
                                                                >
                                                            </div>
                                                        </div>

                                                        <template :key="method.uuid"
                                                                  x-for="document in method.confidantPerson.documentsPerson"
                                                        >
                                                            <div class="form-row-2">
                                                                <div class="form-group"
                                                                     x-data="{
                                                                         documentLabels: @js(__('patients.documents')),
                                                                         getDocumentLabel(type) {
                                                                             return this.documentLabels[type?.toLowerCase()] ?? type
                                                                         }
                                                                     }"
                                                                >
                                                                    <label for="documentType" class="label-modal">
                                                                        {{ __('forms.document_type') }}
                                                                        <span class="text-red-600"></span>
                                                                    </label>
                                                                    <input :value="getDocumentLabel(document.type)"
                                                                           type="text"
                                                                           name="documentType"
                                                                           id="documentType"
                                                                           class="input-modal"
                                                                           autocomplete="off"
                                                                           readonly
                                                                    >
                                                                </div>

                                                                <div class="form-group">
                                                                    <label for="documentNumber" class="label-modal">
                                                                        {{ __('forms.document_number') }}
                                                                    </label>
                                                                    <input type="text"
                                                                           :value="document.number"
                                                                           class="input-modal"
                                                                           name="documentNumber"
                                                                           readonly
                                                                    >
                                                                </div>
                                                            </div>
                                                        </template>

                                                        <div class="form-row-2">
                                                            <div class="form-group">
                                                                <label for="phoneNumber" class="label-modal">
                                                                    {{ __('forms.phone_number') }}
                                                                    <span class="text-red-600"></span>
                                                                </label>
                                                                <input :value="method.confidantPerson.phones.number"
                                                                       type="tel"
                                                                       name="phoneNumber"
                                                                       id="phoneNumber"
                                                                       class="input-modal"
                                                                       autocomplete="off"
                                                                       readonly
                                                                >
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <div class="flex justify-between items-center mt-8">
                                <button type="button" @click="showAuthMethodModal = false" class="button-minor">
                                    {{ __('forms.cancel') }}
                                </button>
                            </div>
                        </div>
                    @else
                        <div wire:key="auth-step-{{ $authStep }}">
                            @include('livewire.person.parts.modals.choose-auth-method-' . $authStep)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
