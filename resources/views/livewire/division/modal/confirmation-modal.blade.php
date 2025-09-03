<div>
    <template x-teleport="body">
        <div
            x-show="divisionId"
            @keydown.escape.window="divisionId = 0"
            class="fixed inset-0 z-50 overflow-y-auto"
            role="dialog"
            aria-modal="true"
            style="display: none;"
        >
            {{-- OVERLAY --}}
            <div
                x-show="divisionId"
                x-transition.opacity
                class="fixed inset-0 bg-black/30"
            ></div>

            {{-- DIALOG  BODY --}}
            <div
                x-show="divisionId"
                x-transition
                @click="show = false"
                class="relative flex min-h-screen items-center justify-center p-4"
            >
                <div
                    @click.stop
                    x-trap.noscroll.inert="divisionId"
                    class="relative w-full max-w-md overflow-hidden rounded-lg bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3
                        x-text="actionTitle"
                        class="text-lg font-medium text-gray-900 dark:text-white"
                    ></h3>

                    <p
                        x-text="textConfirmation"
                        class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                    ></p>

                    <div class="mt-6 flex justify-center gap-6">
                        <button
                            type="button"
                            @click="divisionId = 0"
                            class="alternative-button cursor-pointer mb-0"
                        >
                            {{ __('forms.cancel') }}
                        </button>

                        <button
                            type="button"
                            @click.prevent="$wire.$call(actionType, divisionId); divisionId = 0;"
                            wire:loading.attr="disabled"
                            class="button-danger cursor-pointer mb-[8px]"
                            x-text="actionButtonText"
                        ></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
