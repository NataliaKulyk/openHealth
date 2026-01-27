{{-- Medications Drawer --}}
<template x-teleport="body">
    <div id="medications-drawer-right"
         class="fixed top-0 right-0 z-40 h-screen pt-20 p-4 overflow-y-auto transition-transform translate-x-full bg-white w-4/5 dark:bg-gray-800"
         tabindex="-1"
         aria-labelledby="medications-drawer-label"
    >
        <h3 class="modal-header" id="medications-drawer-label">
            {{ __('treatment-plan.medications') }}
        </h3>

        {{-- Content --}}
        <form>
            <div class="p-4 rounded-lg bg-gray-50 dark:bg-gray-700 mb-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Контент drawer для ліків буде додано пізніше') }}
                </p>
            </div>

            <div class="mt-6 flex justify-between space-x-2">
                <button type="button"
                        class="button-minor"
                        data-drawer-hide="medications-drawer-right"
                        aria-controls="medications-drawer-right"
                >
                    {{ __('forms.cancel') }}
                </button>

                <button type="button"
                        class="button-primary"
                        data-drawer-hide="medications-drawer-right"
                >
                    {{ __('forms.save') }}
                </button>
            </div>
        </form>
    </div>
</template>
