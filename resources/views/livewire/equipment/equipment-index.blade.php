<div x-data>
    <x-header-navigation class="items-start">

        <x-slot name="title">
            {{ __('equipment.equipment') }}
        </x-slot>

        <div class="mt-3 ml-0 flex flex-col sm:flex-row sm:flex-wrap gap-2 self-start">
            <a href=""
               class="button-primary flex items-center gap-2"
            >
                @icon('plus', 'w-4 h-4')
                {{ __('equipment.new_equipment') }}
            </a>

            <button wire:click="sync" type="button" class="button-sync flex items-center gap-2 whitespace-nowrap">
                @icon('refresh', 'w-4 h-4')
                {{ __('forms.synchronise_with_eHealth') }}
            </button>
        </div>

        <x-slot name="navigation">
            <div class="flex flex-col -my-4">
                <form wire:submit.prevent="applyFilters" x-data="{ showFilter: true }">
                    <div class="flex mb-4 flex-col lg:flex-row items-stretch lg:items-end gap-2 lg:gap-4 w-full">

                        <div class="w-full lg:w-96">
                            <label for="search_equipment"
                                   class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
                                @icon('search-outline', 'w-4.5 h-4.5')
                                <span>{{ __('equipment.search_equipment') }}</span>
                            </label>

                            <div class="form-group group w-full">
                                <input type="text"
                                       id="search_equipment"
                                       placeholder=" "
                                       class="input peer"
                                       wire:model.defer="search"
                                       autocomplete="off" />
                                <label for="search_equipment" class="label">{{ __('equipment.name_inventory_number') }}</label>
                            </div>
                        </div>

                        <button class="button-minor flex items-center justify-center gap-2 w-full lg:w-auto self-stretch lg:self-auto lg:-translate-y-[9px]"
                                @click="showFilter = !showFilter">
                            @icon('adjustments', 'w-4 h-4')
                            <span>{{ __('forms.additional_search_parameters') }}</span>
                        </button>
                    </div>

                    <div x-cloak x-show="showFilter" x-transition>
                        <div class="form-row-4">
                            <div class="form-group group">
                                <select wire:model="form.typeMedicalDevice"
                                        name="typeMedicalDevice"
                                        id="typeMedicalDevice"
                                        class="peer input-select"
                                >
                                    <option value="">{{ __('forms.select') }}</option>
                                </select>
                                <label for="typeMedicalDevice" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                                    {{ __('equipment.type_medical_device') }}
                                </label>
                                {{-- @error('form.typeMedicalDevice')
                                <p class="text-error">{{ $message }}</p>
                                @enderror --}}
                            </div>
                            <div class="form-group group">
                                <select wire:model="form.medicalFacility"
                                        name="medicalFacility"
                                        id="medicalFacility"
                                        class="peer input-select"
                                >
                                    <option value="">{{ __('forms.select') }}</option>
                                </select>
                                <label for="medicalFacility" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                                    {{ __('equipment.medical_facility') }}
                                </label>
                                {{-- @error('form.medicalFacility')
                                <p class="text-error">{{ $message }}</p>
                                @enderror --}}
                            </div>
                        </div>
                        <div class="form-row-4">
                            <div class="form-group group" x-data="{ open: false, selectedStatuses: $wire.entangle('form.category').defer }">
                                <label for="categoryFilter" class="label">{{ __('forms.statuteMd5') }}</label>
                                <div class="relative">

                                    <input type="text"
                                           id="categoryFilter"
                                           class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                                           placeholder="{{ __('forms.select') }}"
                                           x-on:click="open = !open"
                                           :value="selectedStatuses.length ? selectedStatuses.map(s => {
                                                    if (s === 'APPROVED') return '{{ __('forms.active') }}';
                                                    if (s === 'NEW') return '{{ __('equipment.non_active') }}';
                                                    if (s === 'DISMISSED') return '{{ __('forms.draft') }}';
                                                    if (s === 'VERIFIED') return '{{ __('equipment.marked_as_incorrect') }}';
                                                    return s;
                                                }).join(', ') : ''"
                                           readonly
                                    />
                                    <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <div x-show="open"
                                         x-on:click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg">
                                        <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="APPROVED" wire:model.defer="form.category"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('forms.active') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="NEW" wire:model.defer="form.category"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.non_active') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="DISMISSED" wire:model.defer="form.category"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('forms.draft') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="VERIFIED" wire:model.defer="form.category"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent">
                                                    <span>{{ __('equipment.marked_as_incorrect') }}</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group group" x-data="{ open: false, selectedStatuses: $wire.entangle('form.availability').defer }">
                                <label for="availabilityFilter" class="label">{{ __('equipment.accessibility') }}</label>
                                <div class="relative">

                                    <input type="text"
                                           id="availabilityFilter"
                                           class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                                           placeholder="{{ __('forms.select') }}"
                                           x-on:click="open = !open"
                                           :value="selectedStatuses.length ? selectedStatuses.map(s => {
                                                    if (s === 'AVAILABLE') return '{{ __('equipment.available') }}';
                                                    if (s === 'DAMAGED') return '{{ __('equipment.damaged') }}';
                                                    if (s === 'DESTROYED') return '{{ __('equipment.destroyed') }}';
                                                    if (s === 'LOST') return '{{ __('equipment.lost') }}';
                                                    return s;
                                                }).join(', ') : ''"
                                           readonly
                                    />
                                    <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                    <div x-show="open"
                                         x-on:click.away="open = false"
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg">
                                        <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="AVAILABLE" wire:model.defer="form.availability"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.available') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="DAMAGED" wire:model.defer="form.availability"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.damaged') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="DESTROYED" wire:model.defer="form.availability"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.destroyed') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="LOST" wire:model.defer="form.availability"
                                                           class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent">
                                                    <span>{{ __('equipment.lost') }}</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-9 mt-6 flex flex-col sm:flex-row gap-2 w-full">
                        <button type="submit" class="flex items-center gap-2 button-primary">
                            @icon('search', 'w-4 h-4')
                            <span>{{ __('forms.search') }}</span>
                        </button>
                        <button type="button" wire:click="resetFilters" class="button-primary-outline-red">
                            {{ __('forms.reset_all_filters') }}
                        </button>
                    </div>
                </form>
            </div>
        </x-slot>
    </x-header-navigation>

    <div class="flow-root mt-8 shift-content pl-3.5">
        <div class="max-w-screen-xl">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table
                    class="w-full table-fixed text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 w-[25%] text-left">{{ __('forms.name') }}</th>
                        <th class="px-6 py-3 w-[10%] text-left">{{ __('equipment.inventory_number') }}</th>
                        <th class="px-6 py-3 w-[15%] text-left">{{ __('forms.type') }}</th>
                        <th class="px-6 py-3 w-[15%] text-left">{{ __('equipment.legal_entity') }}</th>
                        <th class="px-6 py-3 w-[10%] text-left">{{ __('equipment.date_creation') }}</th>
                        <th class="px-6 py-3 w-[10%] text-left">{{ __('equipment.status') }}</th>
                        <th class="px-6 py-3 w-[10%] text-left">{{ __('equipment.accessibility') }}</th>
                        <th class="px-6 py-3 w-[6%] text-center">{{ __('forms.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr {{-- wire:key='equipment-{{ $loop->index }}' --}}
                        class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200"
                    >
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white align-top text-left"
                        >
                            {{-- {{ $item['name'] ?? '' }} --}}
                        </th>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- {{ $item['inventory_number'] ?? '' }} --}}
                        </td>
                        <td class="px-6 py-4 break-words whitespace-normal align-top text-left">
                            {{-- {{ $item['type'] ?? '' }} --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- {{ $item['legal_entity'] ?? '' }} --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- {{ $item['date_creation'] ?? '' }} --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- @if (($item['status'] ?? '') === 'APPROVED')
                                <span class="badge-green">{{ __('forms.active') }}</span>
                            @elseif (($item['status'] ?? '') === 'NEW')
                                <span class="badge-red">{{ __('equipment.non_active') }}</span>
                            @elseif (($item['status'] ?? '') === 'DISMISSED')
                                <span class="badge-yellow">{{ __('forms.draft') }}</span>
                            @elseif (($item['status'] ?? '') === 'VERIFIED')
                                <span class="badge-red">{{ __('equipment.marked_as_incorrect') }}</span>
                            @endif --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- @if (($item['availability'] ?? '') === 'AVAILABLE')
                                <span class="badge-green">{{ __('equipment.available') }}</span>
                            @elseif (($item['availability'] ?? '') === 'DAMAGED')
                                <span class="badge-red">{{ __('equipment.damaged') }}</span>
                            @elseif (($item['availability'] ?? '') === 'DESTROYED')
                                <span class="badge-red">{{ __('equipment.destroyed') }}</span>
                            @elseif (($item['availability'] ?? '') === 'LOST')
                                <span class="badge-red">{{ __('equipment.lost') }}</span>
                            @endif --}}
                        </td>
                        <td class="px-6 py-4 text-center align-top">
                            <a href="#"
                               class="inline-flex items-center justify-center w-full text-sm text-gray-600 hover:text-blue-600 transition-colors"
                               title="{{ __('forms.edit') }}">
                                {{-- @icon('edit-user-outline', 'w-4 h-4') --}}
                            </a>
                        </td>
                    </tr>
                    {{-- <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                            {{ __('forms.no_records_found') }}
                        </td>
                    </tr> --}}
                    </tbody>
                </table>
            </div>

            <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
                {{-- {{ ->links() }} --}}
            </div>
        </div>
    </div>

</div>
