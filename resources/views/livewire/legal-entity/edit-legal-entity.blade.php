<div>
    <x-section-navigation x-data="{ showFilter: false }" class="">
        <x-slot name="title">{{ __('Редагувати заклад ') }}</x-slot>
    </x-section-navigation>

    <section class="section-form"
        x-data="{
            activeStep: 0,
            isEdit: @json($isEdit),
            openModal: false,
            isTermDisabled: @entangle('legalEntityForm.publicOffer.consent').defer,
            init() {
                Livewire.hook('commit', ({ succeed }) => {
                    succeed(() => {
                        this.$nextTick(() => {
                            const firstErrorMessage = document.querySelector('.error-message')

                            if (firstErrorMessage !== null) {
                                firstErrorMessage.scrollIntoView({ block: 'center', inline: 'center' });
                                this.isTermDisabled = false;
                            }
                        })
                    })
                })
            }
        }"
    >
        <div class="form-row">
            <form
                id="edit_legal_entity_form"
                class="grid-cols-1"
            >
                <div class="p-6.5">
                    @include('livewire.legal-entity.step._step_edrpou')
                    @include('livewire.legal-entity.step._step_owner')
                    @include('livewire.legal-entity.step._step_contact')
                    @include('livewire.legal-entity.step._step_residence_address')
                    @include('livewire.legal-entity.step._step_accreditation')
                    @include('livewire.legal-entity.step._step_license')
                    @include('livewire.legal-entity.step._step_additional_information')
                    @include('livewire.legal-entity.step._step_public_offer')
                </div>

                <div class="mt-6 flex flex-col gap-6 xl:flex-row justify-between items-center">
                    {{-- Agreement checkbox --}}
                    <div class="form-group group">
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                value="isTermDisabled"
                                id="public_offer_consent"
                                class="steps-agreement_checkbox"
                                x-model="isTermDisabled"
                                wire:model="legalEntityForm.publicOffer.consent"
                                :checked="isTermDisabled"
                            />
                            <label
                                for="public_offer_consent"
                                class="steps-agreement_label"
                            >
                                {{__('forms.agree')}}
                                <button
                                    type="button"
                                    class="steps-agreement_button cursor-pointer"
                                    x-ref="openModalBtn"
                                     @click="
                                        $el.blur(); // Remove focus before Modal opening or else aria werning shows in console
                                        openModal = true;
                                    "
                                >
                                    {{ __('forms.withTerms') }}
                                </button>
                            </label>
                        </div>
                    </div>

                    {{-- Submit button --}}
                    <div class="xl:w-1/4 flex justify-end">
                        <button
                            type="button"
                            class="button-primary cursor-pointer"
                            wire:click="updateLegalEntity"
                            :disabled="!isTermDisabled"
                        >
                            {{ __('forms.sendRequest') }}
                        </button>
                    </div>
                </div>
            </form>

            <!-- Main modal -->
            <div
                x-cloak
                role="dialog"
                tabindex="-1"
                aria-modal="true"
                x-show="openModal"
                x-id="['le-modal-term']"
                :aria-labelledby="$id('le-modal-term')"
                class="fixed inset-0 z-[100] overflow-y-auto"
                x-on:keydown.escape.prevent.stop="openModal = false"
            >
                <!-- Modal overlay -->
                <div
                    x-show="openModal"
                    @click="openModal = false"
                    class="fixed z-20 inset-0 bg-black/25"
                ></div>

                <!-- Modal content -->
                <div
                    x-on:click.stop
                    x-trap.noscroll.inert="openModal"
                    tabindex="-1"
                    x-ref="modalContent"
                    x-show="openModal"
                    x-init="$watch('openModal', value => { if(value) { $nextTick(() => { $refs.modalContent.focus() }) } })"
                    class="relative w-[70%] h-[75vh] rounded-xl bg-white shadow-lg mx-auto mt-[15vh] flex flex-col dark:bg-gray-800 z-22"
                >
                    <!-- Modal header -->
                    <div class="p-6 border-b mb-2">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900 dark:text-white">Terms of Use</h3>
                            <p class="block text-xs text-gray-900 dark:text-white">Last updated 01.01.2025</p>
                        </div>

                        <button
                            type="button"
                            @click="openModal = false"
                            class="absolute top-4 right-4 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center"
                        >
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6" />
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>

                    <!-- Modal body -->
                    <div class="flex-1 overflow-y-auto p-6">
                        @include('components.terms')
                    </div>

                    <!-- Modal footer -->
                    <div class="flex gap-4 flex-wrap justify-center p-4 border-t mt-2 mx-auto">
                        <button
                            type="button"
                            class="alternative-button cursor-pointer"
                            @click="openModal = false;"
                        >
                            {{ __('forms.close') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <x-forms.loading />
    </section>
</div>
