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
                    class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 text-center shadow-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800"
                >
                    <h3
                        x-text="actionTitle"
                        class="text-xl font-bold text-gray-900 dark:text-white"
                    ></h3>

                    <p
                        x-text="textConfirmation"
                        class="mt-2 text-sm text-gray-600 dark:text-gray-400"
                    ></p>

                    <div class="mt-6 flex justify-center gap-4">
                        <button
                            type="button"
                            @click="divisionId = 0"
                            class="button-secondary"
                        >
                            {{ __('forms.cancel') }}
                        </button>

                        <button
                            type="button"
                            @click.prevent="$wire.$call(actionType, divisionId); divisionId = 0;"
                            wire:loading.attr="disabled"
                            class="inline-flex justify-center rounded-lg border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                            x-text="actionButtonText"
                        ></button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
