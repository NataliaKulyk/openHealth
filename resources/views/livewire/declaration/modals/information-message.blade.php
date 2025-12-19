<div x-data="{ showInformationMessageModal: $wire.entangle('showInformationMessageModal'), isInformed: true }">
    <template x-teleport="body">
        <div x-show="showInformationMessageModal"
             style="display: none"
             @keydown.escape.prevent.stop="showInformationMessageModal = false"
             role="dialog"
             aria-modal="true"
             class="modal"
        >
            <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-transition @click="showInformationMessageModal = false" class="modal-wrapper">
                <div @click.stop x-trap.noscroll.inert="showInformationMessageModal"
                     class="modal-content w-full max-w-4xl mx-auto"
                >
                    <h2 class="mb-12 text-2xl font-semibold text-gray-900 dark:text-white text-center">
                        {{ __('declarations.confirmation_of_application_for_registration_of_declaration') }}
                    </h2>

                    <ul class="list-disc list-inside mb-8">
                        <p class="default-p">Ви, як медичний працівник закладу охорони здоров'я:</p>
                        <li class="default-p pl-2">підтверджуєте, що пацієнта як особу ідентифіковано:</li>
                        <li class="default-p pl-2">підтверджуєте, що повідомили пацієнту або його представнику мету та підстави обробки його персональних даних.</li>
                        <p class="default-p">ПАМ'ЯТКА ПАЦІЄНТУ</p>
                        <p class="default-p">Надаючи код з СМС повідомлення або документи (при попередній реєстрації пацієнта в системі за документами) особа чи її представник:</p>
                        <li class="default-p pl-2">надає згоду медичному працівнику на обробку персональних даних пацієнта:</li>
                        <li class="default-p pl-2">надає згоду медичному працівнику на подання декларації про вибір лікаря, який надає первинну медичну допомогу в електронну систему охорони здоров'я.</li>
                    </ul>

                    {{-- Is signed by patient --}}
                    <div class="form-row">
                        <div class="form-group group">
                            <input x-model="isInformed"
                                   type="checkbox"
                                   name="isInformed"
                                   id="isInformed"
                                   class="default-checkbox"
                            />
                            <label class="default-p" for="isInformed">
                                {{ __('declarations.patient_confirm_information_message') }}
                            </label>
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="flex justify-center gap-8.5 mt-16">
                        <button type="button" @click="showInformationMessageModal = false" class="button-minor">
                            {{ __('forms.cancel') }}
                        </button>
                        <button wire:click="openApproveModal"
                                type="button"
                                class="button-primary flex items-center gap-2"
                                :disabled="!isInformed"
                        >
                            {{ __('forms.confirm') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
