{{-- MODAL FOR DISMISSAL --}}
<div x-data="{ showDismissModal: @entangle('showDismissModal') }">
    <template x-teleport="body">
        <div x-show="showDismissModal" style="display: none" @keydown.escape.prevent.stop="showDismissModal = false" role="dialog" aria-modal="true" class="fixed inset-0 z-50 overflow-y-auto">
            <div x-show="showDismissModal" x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-show="showDismissModal" x-transition @click="showDismissModal = false" class="relative flex min-h-screen items-center justify-center p-4">
                <div @click.stop x-trap.noscroll.inert="showDismissModal" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        {{ __('forms.action_confirmation') }}: {{ $employeeToDismissName ?? '' }} ({{ __('forms.dismiss') }})
                    </h2>
                    <p class="mt-4 text-sm text-gray-600 whitespace-pre-line dark:text-gray-300">
                        {{ __('employees.dismissalWarning') }}
                    </p>
                    <div class="mt-6 flex justify-center gap-4">
                        <button type="button" @click="showDismissModal = false" class="button-primary">{{ __('forms.cancel') }}</button>
                        <button type="button" wire:click="dismissed" wire:loading.attr="disabled" class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            {{ __('forms.dismiss') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
