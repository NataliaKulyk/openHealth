<div x-data="{ showSignModal: $wire.entangle('showSignModal') }">
    <template x-teleport="body">
        <div x-show="showSignModal"
             style="display: none"
             @keydown.escape.prevent.stop="showSignModal = false"
             role="dialog"
             aria-modal="true"
             class="modal"
        >
            <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-transition @click="showSignModal = false" class="modal-wrapper">
                <div @click.stop x-trap.noscroll.inert="showSignModal"
                     class="modal-content w-full max-w-4xl mx-auto"
                >
                    <h2 class="mb-8 text-2xl font-semibold text-gray-900 dark:text-white text-center">
                        {{ __('declarations.confirmation_of_patient_signature_on_declaration_application') }}
                    </h2>

                    <ol class="list-decimal list-inside mb-8">
                        <li class="default-p mb-4">{{ __('Роздрукуйте заявку на декларацію в двох екземплярах з метою перевірки та підписання пацієнтом або його законним представником') }}</li>
                        <button x-data
                                @click="
                                    let printWindow = window.open('', '_blank');
                                    printWindow.document.write($wire.printableContent);
                                    printWindow.document.close();
                                    printWindow.focus();
                                    printWindow.print();
                                "
                                class="button-minor gap-3"
                        >
                            <svg class="w-4 h-4 text-gray-800 dark:text-white" aria-hidden="true"
                                 xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                 viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linejoin="round" stroke-width="2"
                                      d="M16.444 18H19a1 1 0 0 0 1-1v-5a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v5a1 1 0 0 0 1 1h2.556M17 11V5a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v6h10ZM7 15h10v4a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-4Z"/>
                            </svg>
                            {{ __('declarations.print_application') }}
                        </button>
                        <li class="default-p mt-8">{{ __('Підтвердіть, що заявка на декларацію підписана пацієнтом або його законним представником') }}</li>
                    </ol>

                    {{-- Is signed by patient --}}
                    <div x-data="{ isSigned: true }">
                        <div class="form-row">
                            <div class="form-group group">
                                <input x-model="isSigned"
                                       type="checkbox"
                                       name="isSigned"
                                       id="isSigned"
                                       class="default-checkbox"
                                       checked
                                />
                                <label class="default-p" for="isSigned">
                                    {{ __('Декларація про вибір лікаря, який надає первинну медичну допомогу підписана пацієнтом') }}
                                </label>
                            </div>
                        </div>

                        {{-- Action buttons --}}
                        <div class="flex justify-center gap-4 mt-16">
                            <button type="button" @click="showSignModal = false" class="button-danger">
                                {{__('forms.cancel')}}
                            </button>
                            <button wire:click="openSignatureModal"
                                    type="button"
                                    class="button-primary flex items-center gap-2"
                                    :disabled="!isSigned"
                            >
                                <svg aria-hidden="true" width="16" height="17" viewBox="0 0 16 17" fill="none"
                                     xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                          d="M14.3996 6.90084C14.3998 7.65058 14.2244 8.38994 13.8874 9.05968C13.5505 9.72942 13.0613 10.3109 12.4591 10.7575C11.8569 11.2042 11.1585 11.5035 10.4198 11.6317C9.68106 11.7598 8.92261 11.713 8.20521 11.4952L7.99961 11.7008L7.19961 12.5008L6.39961 13.3008H4.79961V14.9008H1.59961V11.7008L5.00521 8.29524C4.80557 7.63525 4.75046 6.94 4.84364 6.2568C4.93683 5.5736 5.17611 4.9185 5.5452 4.33608C5.9143 3.75366 6.40454 3.2576 6.98256 2.88166C7.56059 2.50572 8.21282 2.25872 8.89487 2.15749C9.57692 2.05625 10.2728 2.10315 10.9351 2.29499C11.5974 2.48683 12.2106 2.81911 12.7329 3.26921C13.2553 3.71932 13.6745 4.27667 13.9621 4.90335C14.2497 5.53003 14.3989 6.21132 14.3996 6.90084V6.90084ZM9.59961 3.70084C9.38744 3.70084 9.18395 3.78513 9.03392 3.93516C8.88389 4.08519 8.79961 4.28867 8.79961 4.50084C8.79961 4.71301 8.88389 4.9165 9.03392 5.06653C9.18395 5.21656 9.38744 5.30084 9.59961 5.30084C10.024 5.30084 10.4309 5.46941 10.731 5.76947C11.031 6.06953 11.1996 6.4765 11.1996 6.90084C11.1996 7.11302 11.2839 7.3165 11.4339 7.46653C11.584 7.61656 11.7874 7.70084 11.9996 7.70084C12.2118 7.70084 12.4153 7.61656 12.5653 7.46653C12.7153 7.3165 12.7996 7.11302 12.7996 6.90084C12.7996 6.05215 12.4625 5.23822 11.8624 4.6381C11.2622 4.03798 10.4483 3.70084 9.59961 3.70084Z"
                                          fill="white"/>
                                </svg>
                                {{ __('forms.sign_with_KEP') }}
                                <svg class="w-6 h-6 text-white dark:text-white" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                     viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2" d="M19 12H5m14 0-4 4m4-4-4-4"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
