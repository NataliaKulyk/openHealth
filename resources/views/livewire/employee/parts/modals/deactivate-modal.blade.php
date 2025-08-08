<div x-data="{ show: @entangle('showDeactivateModal') }">
    <template x-teleport="body">
        <div x-show="show" style="display: none" @keydown.escape.prevent.stop="show = false" role="dialog" aria-modal="true" class="fixed inset-0 z-50 overflow-y-auto">
            <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/30"></div>
            <div x-show="show" x-transition @click="show = false" class="relative flex min-h-screen items-center justify-center p-4">
                <div @click.stop x-trap.noscroll.inert="show" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 text-center shadow-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800">

                    @if($showDeactivateModal)
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ __('employees.modals.deactivate.title_with_name', ['name' => $employeeToDeactivateName]) }}
                        </h2>

                        <div class="mt-4 p-4 text-sm text-left text-gray-700 bg-gray-50 rounded-lg dark:bg-gray-700 dark:text-gray-300" role="alert">
                            {{ __('employees.dismissalWarning') }}
                        </div>

                        <div class="mt-6 flex justify-center gap-4">
                            <button type="button" @click="show = false" class="button-secondary">{{ __('forms.cancel') }}</button>
                            <button type="button" wire:click="deactivate" wire:loading.attr="disabled" class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                {{ __('forms.deactivate') }}
                            </button>
                        </div>
                    @endif
            </div>
        </div>
    </template>
</div>
