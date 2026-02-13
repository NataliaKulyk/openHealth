{{-- Modal for terminating relationship with representative --}}
<div x-data="{
    showTerminateModal: Alpine.store('authDrawer').showTerminateModal || false
}"
     @keydown.escape.window="Alpine.store('authDrawer').showTerminateModal = false"
     wire:ignore.self
>
    <template x-teleport="body">
        <div x-show="Alpine.store('authDrawer').showTerminateModal"
             style="display: none"
             class="fixed inset-0 z-[100] overflow-y-auto"
             role="dialog"
             aria-modal="true"
        >
            {{-- Overlay --}}
            <div x-show="Alpine.store('authDrawer').showTerminateModal"
                 x-transition.opacity
                 class="fixed inset-0 bg-black/50"
            ></div>

            {{-- Modal Content --}}
            <div x-show="Alpine.store('authDrawer').showTerminateModal"
                 x-transition
                 @click="Alpine.store('authDrawer').showTerminateModal = false"
                 class="relative flex min-h-screen items-center justify-center p-4"
            >
                <div @click.stop
                     x-trap.noscroll.inert="Alpine.store('authDrawer').showTerminateModal"
                     class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 shadow-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800"
                >
                    {{-- Title --}}
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">
                        {{ __('patients.relationship_terminated') }}
                    </h2>

                    {{-- Warning Box --}}
                    <div role="alert" class="mb-6 p-4 rounded-lg" style="background-color: #FFFBE6;">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @icon('alert-circle', 'w-5 h-5 text-gray-600 dark:text-gray-400')
                            </div>
                            <div class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                                <p class="mb-2">
                                    {{ __('patients.terminate_relationship_warning_1') }}
                                </p>
                                <p>
                                    {{ __('patients.terminate_relationship_warning_2') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex justify-end gap-4">
                        <button type="button"
                                @click="Alpine.store('authDrawer').showTerminateModal = false"
                                class="button-minor"
                        >
                            {{ __('forms.cancel') }}
                        </button>

                        <button type="button"
                                @click="Alpine.store('authDrawer').showTerminateModal = false; Alpine.store('authDrawer').showSignatureDrawer = false; Alpine.store('authDrawer').showAuthSmsDrawer = false"
                                class="button-outline-primary"
                        >
                            {{ __('patients.to_authentication_methods') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
