<div x-data="{ showApproveModal: $wire.entangle('showApproveModal') }">
    <template x-teleport="body">
        <div x-show="showApproveModal"
             style="display: none"
             @keydown.escape.prevent.stop="showApproveModal = false"
             role="dialog"
             aria-modal="true"
             class="modal"
        >
            <div x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-transition @click="showApproveModal = false"
                 class="modal-wrapper"
            >
                <div @click.stop x-trap.noscroll.inert="showApproveModal"
                     class="modal-content w-full max-w-5xl mx-auto"
                >
                    <h2 class="mb-8 text-2xl font-semibold text-gray-900 dark:text-white text-center">
                        {{ __('declarations.confirmation_of_application_for_registration_of_declaration') }}
                    </h2>

{{--                    <div class="w-full h-[80vh]">--}}
{{--                        <iframe class="w-full h-full border" srcdoc="{{ $content }}"></iframe>--}}
{{--                    </div>--}}

                    <div class="form-row-3">
                        <div class="form-group group">
                            <input type="checkbox"
                                   name="isDiagnosticReferralAvailable"
                                   id="isDiagnosticReferralAvailable"
                                   class="default-checkbox mb-1"
                            />
                            <label class="default-p" for="isDiagnosticReferralAvailable">
                                {{ __('patients.referral_available') }}
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
