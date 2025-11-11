<div x-data="{ show: $wire.entangle('show-update-status-modal') }">
    <template x-teleport="body">
        <div x-show="show"
             style="display: none"
             @keydown.escape.prevent.stop="show = false"
             role="dialog"
             aria-modal="true"
             class="fixed inset-0 z-50 overflow-y-auto"
        >
            <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/30"></div>

            <div x-show="show"
                 x-transition
                 @click="show = false"
                 class="relative flex min-h-screen items-center justify-center p-4"
            >
                <div @click.stop
                     x-trap.noscroll.inert="show"
                     class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 text-center shadow-xl border border-gray-200 dark:border-gray-700 dark:bg-gray-800"
                >

                    @if($showUpdateStatusModal)
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 text-left">
                            {{ __('equipments.update_equipment_status', ['name' => $equipmentName]) }}
                        </h2>

                        <form wire:submit.prevent="updateStatus">
                            <div class="mb-4 text-left">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('forms.status.label') }}
                                </label>
                                <select id="status"
                                        wire:model.defer="newStatus"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                >
                                    <option value="">{{ __('forms.select') }}</option>
                                </select>
                                @error('newStatus') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="mb-6 text-left">
                                <label for="reason" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ __('equipments.reason_for_status_change') }}
                                </label>
                                <textarea id="reason"
                                          wire:model.defer="reason"
                                          rows="4"
                                          placeholder=" "
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300"
                                ></textarea>
                                @error('reason') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" @click="show = false" class="button-minor">
                                    {{ __('forms.cancel') }}
                                </button>
                                <button type="submit"
                                        wire:loading.attr="disabled"
                                        class="inline-flex justify-center rounded-lg border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-75"
                                >
                                    {{ __('forms.update_data') }}
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
