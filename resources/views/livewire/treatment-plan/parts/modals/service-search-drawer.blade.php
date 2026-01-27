{{-- Service Search Drawer (nested, overlays services drawer) --}}
<template x-teleport="body">
    {{-- Backdrop overlay - click to return to services drawer --}}
    <div id="service-search-drawer-backdrop"
         class="fixed inset-0 bg-gray-500/50 transition-opacity cursor-pointer"
         style="z-index: 45; display: none;"
         data-drawer-hide="service-search-drawer-right"
         aria-hidden="true"
    ></div>

    <div id="service-search-drawer-right"
         class="fixed top-0 right-0 z-50 h-screen pt-20 p-4 overflow-y-auto transition-transform translate-x-full bg-white dark:bg-gray-800"
         style="width: calc(80% - 30px);"
         tabindex="-1"
         aria-labelledby="service-search-drawer-label"
    >
        <h3 class="modal-header" id="service-search-drawer-label">
            {{ __('treatment-plan.search_service') }}
        </h3>

        {{-- Search Input --}}
        <div class="mb-4">
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                    @icon('search-outline', 'w-5 h-5 text-gray-500')
                </div>
                <input type="text"
                       class="input peer ps-10 w-full"
                       placeholder="Киснева терапія"
                />
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap gap-2 mb-6">
            <button type="button" class="button-primary flex items-center gap-2">
                @icon('search', 'w-4 h-4')
                <span>{{ __('forms.search') }}</span>
            </button>
            <button type="button" class="button-primary-outline-red">
                {{ __('forms.reset_all_filters') }}
            </button>
            <button type="button" class="button-filter flex items-center gap-2">
                @icon('adjustments', 'w-4 h-4')
                <span>{{ __('forms.additional_search_parameters') }}</span>
            </button>
        </div>

        {{-- Filters --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div class="form-group group">
                <label class="label">
                    {{ __('treatment-plan.service_category') }}
                </label>
                <select class="input-select peer w-full">
                    <option selected value="">{{ __('treatment-plan.procedures_on_nervous_system') }}</option>
                </select>
            </div>
            <div class="form-group group">
                <label class="label">
                    {{ __('treatment-plan.service_group_active') }}
                </label>
                <select class="input-select peer w-full">
                    <option selected value="yes">{{ __('treatment-plan.yes') }}</option>
                </select>
            </div>
            <div class="form-group group">
                <label class="label">
                    {{ __('treatment-plan.service_active') }}
                </label>
                <select class="input-select peer w-full">
                    <option selected value="yes">{{ __('treatment-plan.yes') }}</option>
                </select>
            </div>
            <div class="form-group group">
                <label class="label">
                    {{ __('treatment-plan.allowed_in_em') }}
                </label>
                <select class="input-select peer w-full">
                    <option selected value="yes">{{ __('treatment-plan.yes') }}</option>
                </select>
            </div>
        </div>

        {{-- Results Table --}}
        <div class="overflow-x-auto mb-6">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('treatment-plan.name') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('treatment-plan.allowed_in_em_short') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('treatment-plan.code') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('treatment-plan.status') }}</th>
                        <th scope="col" class="px-4 py-3 font-medium">{{ __('treatment-plan.action') }}</th>
                    </tr>
                </thead>
                <tbody x-data="{ expanded: null }">
                    {{-- Group 1: Направлення до спеціаліста --}}
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="expanded = expanded === 1 ? null : 1" class="text-gray-500">
                                    <svg class="w-4 h-4 transition-transform" :class="expanded === 1 ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">Направлення до спеціаліста</p>
                                    <p class="text-xs text-gray-500">e1230-0f3</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">+</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('treatment-plan.active') }}</span>
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>

                    {{-- Group 2: Лікувально-діагностичні процедури --}}
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="expanded = expanded === 2 ? null : 2" class="text-gray-500">
                                    <svg class="w-4 h-4 transition-transform" :class="expanded === 2 ? 'rotate-90' : ''" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <span class="font-medium text-gray-900 dark:text-white">Лікувально-діагностичні процедури</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">+</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">{{ __('treatment-plan.inactive') }}</span>
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>

                    {{-- Group 3: Діагностичні процедури (expanded) --}}
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="expanded = expanded === 3 ? null : 3" class="text-gray-500">
                                    <svg class="w-4 h-4 transition-transform" :class="expanded === 3 ? 'rotate-90' : 'rotate-90'" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <span class="font-medium text-gray-900 dark:text-white">Діагностичні процедури</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-500">+</td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('treatment-plan.active') }}</span>
                        </td>
                        <td class="px-4 py-3"></td>
                    </tr>

                    {{-- Nested items for Group 3 --}}
                    <tr class="bg-gray-50 dark:bg-gray-900">
                        <td class="px-4 py-3 ps-12">
                            <div>
                                <p class="text-gray-900 dark:text-white">Електрокардіографія</p>
                                <p class="text-xs text-gray-500">e12313-0f3</p>
                            </div>
                        </td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">13121-123</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('treatment-plan.active') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" class="text-blue-500 hover:text-blue-700">
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.757v8.486M7.757 12h8.486M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            </button>
                        </td>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-gray-900">
                        <td class="px-4 py-3 ps-12">
                            <span class="text-gray-900 dark:text-white">Електрокардіографія</span>
                        </td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">31221-123</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('treatment-plan.active') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" class="text-blue-500 hover:text-blue-700">
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.757v8.486M7.757 12h8.486M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            </button>
                        </td>
                    </tr>
                    <tr class="bg-gray-50 dark:bg-gray-900">
                        <td class="px-4 py-3 ps-12">
                            <span class="text-gray-900 dark:text-white">Електрокардіографія</span>
                        </td>
                        <td class="px-4 py-3"></td>
                        <td class="px-4 py-3 text-gray-900 dark:text-white">5435-123</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('treatment-plan.active') }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <button type="button" class="text-blue-500 hover:text-blue-700">
                                <svg class="w-5 h-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 7.757v8.486M7.757 12h8.486M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        <div class="mt-6">
            <button type="button"
                    class="button-minor"
                    data-drawer-hide="service-search-drawer-right"
                    aria-controls="service-search-drawer-right"
            >
                {{ __('forms.cancel') }}
            </button>
        </div>
    </div>
</template>

<script>
    function initServiceSearchDrawer() {
        const drawer = document.getElementById('service-search-drawer-right');
        const backdrop = document.getElementById('service-search-drawer-backdrop');

        if (!drawer || !backdrop) {
            // Elements not ready yet, retry
            setTimeout(initServiceSearchDrawer, 100);
            return;
        }

        // Create a MutationObserver to watch for class changes on the drawer
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    // Check if drawer is visible (doesn't have translate-x-full)
                    if (!drawer.classList.contains('translate-x-full')) {
                        backdrop.style.display = 'block';
                    } else {
                        backdrop.style.display = 'none';
                    }
                }
            });
        });

        observer.observe(drawer, { attributes: true });

        // Click on backdrop to close drawer
        backdrop.addEventListener('click', function() {
            drawer.classList.add('translate-x-full');
            backdrop.style.display = 'none';
        });
    }

    // Start initialization after a short delay to allow Alpine.js to teleport elements
    setTimeout(initServiceSearchDrawer, 500);
</script>
