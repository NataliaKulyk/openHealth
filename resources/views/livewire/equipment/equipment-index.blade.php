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
                            <x-forms.form-group>
                                <x-slot name="label">
                                    <label for="search_equipment"
                                           class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
                                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                  stroke-width="2" d="m19 19-4-4m0-7A7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                        </svg>
                                        <span>{{ __('equipment.search_equipment') }}</span>
                                    </label>
                                </x-slot>
                                <x-slot name="input">
                                    <div class="form-group group w-full">
                                        <input type="text"
                                               id="search_equipment"
                                               placeholder=" "
                                               class="input peer"
                                               wire:model.defer="search"
                                               autocomplete="off" />
                                        <label for="search_equipment" class="label">{{ __('equipment.name_inventory_number') }}</label>
                                    </div>
                                </x-slot>
                            </x-forms.form-group>
                        </div>
                        <button class="button-minor flex items-center justify-center gap-2 w-full lg:w-auto self-stretch lg:self-auto lg:-translate-y-[9px]"
                                @click="showFilter = !showFilter">
                            <svg width="16" height="16" id="svg-adjustments" viewBox="0 0 16 16" fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.00003 3.1999C4.00003 2.98773 3.91574 2.78425 3.76571 2.63422C3.61568 2.48419 3.41220 2.39990 3.20003 2.39990C2.98785 2.39990 2.78437 2.48419 2.63434 2.63422C2.48431 2.78425 2.40003 2.98773 2.40003 3.1999V9.0143C2.15682 9.15474 1.95485 9.35672 1.81444 9.59994C1.67402 9.84316 1.60010 10.11910 1.60010 10.39990C1.60010 10.68070 1.67402 10.95660 1.81444 11.1999C1.95485 11.44310 2.15682 11.64510 2.40003 11.7855V12.7999C2.40003 13.01210 2.48431 13.21560 2.63434 13.3656C2.78437 13.51560 2.98785 13.59990 3.20003 13.5999C3.41220 13.59990 3.61568 13.51560 3.76571 13.3656C3.91574 13.21560 4.00003 13.01210 4.00003 12.7999V11.7855C4.24324 11.64510 4.44520 11.44310 4.58562 11.1999C4.72603 10.95660 4.79996 10.68070 4.79996 10.39990C4.79996 10.11910 4.72603 9.84316 4.58562 9.59994C4.44520 9.35672 4.24324 9.15474 4.00003 9.0143V3.1999ZM8.80003 3.1999C8.80003 2.98773 8.71574 2.78425 8.56571 2.63422C8.41568 2.48419 8.21220 2.39990 8.00003 2.39990C7.78785 2.39990 7.58437 2.48419 7.43434 2.63422C7.28431 2.78425 7.20003 2.98773 7.20003 3.1999V4.2143C6.95682 4.35474 6.75485 4.55672 6.61444 4.79994C6.47402 5.04316 6.40010 5.31906 6.40010 5.59990C6.40010 5.88075 6.47402 6.15665 6.61444 6.39987C6.75485 6.64309 6.95682 6.84507 7.20003 6.9855V12.7999C7.20003 13.01210 7.28431 13.21560 7.43434 13.3656C7.58437 13.51560 7.78785 13.59990 8.00003 13.5999C8.21220 13.59990 8.41568 13.51560 8.56571 13.3656C8.71574 13.21560 8.80003 13.01210 8.80003 12.7999V6.9855C9.04324 6.84507 9.24520 6.64309 9.38562 6.39987C9.52603 6.15665 9.59996 5.88075 9.59996 5.59990C9.59996 5.31906 9.52603 5.04316 9.38562 4.79994C9.24520 4.55672 9.04324 4.35474 8.80003 4.2143V3.1999ZM12.8 2.3999C13.0122 2.3999 13.2157 2.48419 13.3657 2.63422C13.5157 2.78425 13.6 2.98773 13.6 3.1999V9.0143C13.8432 9.15474 14.0452 9.35672 14.1856 9.59994C14.3260 9.84316 14.4 10.11910 14.4 10.39990C14.4 10.68070 14.3260 10.95660 14.1856 11.1999C14.0452 11.44310 13.8432 11.64510 13.6 11.7855V12.7999C13.6 13.01210 13.5157 13.21560 13.3657 13.3656C13.2157 13.51560 13.0122 13.59990 12.8 13.5999C12.5879 13.59990 12.3844 13.51560 12.2343 13.3656C12.0843 13.21560 12 13.01210 12 12.7999V11.7855C11.7568 11.64510 11.5549 11.44310 11.4144 11.1999C11.2740 10.95660 11.2001 10.68070 11.2001 10.39990C11.2001 10.11910 11.2740 9.84316 11.4144 9.59994C11.5549 9.35672 11.7568 9.15474 12 9.0143V3.1999C12 2.98773 12.0843 2.78425 12.2343 2.63422C12.3844 2.48419 12.5879 2.39990 12.8 2.3999V2.3999Z"
                                    fill="currentColor"/>
                            </svg>
                            <span>{{ __('forms.additional_search_parameters') }}</span>
                        </button>
                    </div>

                    <div x-cloak x-show="showFilter" x-transition>
                        <div class="form-row-4">
                            <div class="form-group group">
                                <select {{-- wire:model="form.typeMedicalDevice" --}}
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
                                <select {{-- wire:model="form.medicalFacility" --}}
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
                            <div class="form-group group" x-data="{ open: false, selectedStatuses: /* @entangle('form.category') */ ['APPROVED'] }">
                                <label for="categoryFilter" class="label">{{ __('forms.statuteMd5') }}</label>
                                <div class="relative">

                                    <input type="text"
                                           id="categoryFilter"
                                           class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                                           placeholder="{{ __('forms.select') }}"
                                           x-on:click="open = !open"
                                           :value="selectedStatuses.length ? selectedStatuses.map(s => {
                                                    /* if (s === 'APPROVED') return '{{ __('forms.active') }}';
                                                    if (s === 'NEW') return '{{ __('equipment.non_active') }}';
                                                    if (s === 'DISMISSED') return '{{ __('forms.draft') }}';
                                                    if (s === 'VERIFIED') return '{{ __('equipment.marked_as_incorrect') }}'; */
                                                    return 'Активний, Неактивний';
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
                                                    <input type="checkbox" value="APPROVED" {{-- wire:model.defer="form.category" --}}
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('forms.active') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="NEW" {{-- wire:model.defer="form.category" --}}
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.non_active') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="DISMISSED" {{-- wire:model.defer="form.category" --}}
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('forms.draft') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="VERIFIED" {{-- wire:model.defer="form.category" --}}
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent">
                                                    <span>{{ __('equipment.marked_as_incorrect') }}</span>
                                                </label>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group group" x-data="{ open: false, selectedStatuses: /* @entangle('form.availability') */ ['AVAILABLE'] }">
                                <label for="availabilityFilter" class="label">{{ __('equipment.accessibility') }}</label>
                                <div class="relative">

                                    <input type="text"
                                           id="availabilityFilter"
                                           class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                                           placeholder="{{ __('forms.select') }}"
                                           x-on:click="open = !open"
                                           :value="selectedStatuses.length ? selectedStatuses.map(s => {
                                                    /* if (s === 'AVAILABLE') return '{{ __('equipment.available') }}';
                                                    if (s === 'DAMAGED') return '{{ __('equipment.damaged') }}';
                                                    if (s === 'DESTROYED') return '{{ __('equipment.destroyed') }}';
                                                    if (s === 'LOST') return '{{ __('equipment.lost') }}'; */
                                                    return 'Доступний, Пошкоджений';
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
                                                    <input type="checkbox" value="AVAILABLE" {{-- wire:model.defer="form.availability" --}}
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.available') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="DAMAGED" {{-- wire:model.defer="form.availability" --}}
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.damaged') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="DESTROYED" {{-- wire:model.defer="form.availability" --}}
                                                    class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                                    <span>{{ __('equipment.destroyed') }}</span>
                                                </label>
                                            </li>
                                            <li>
                                                <label class="flex items-center space-x-2 cursor-pointer">
                                                    <input type="checkbox" value="LOST" {{-- wire:model.defer="form.availability" --}}
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

                        <button type="button" {{-- wire:click="resetFilters" --}} class="button-primary-outline-red">
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
                            {{-- {{ $item['name'] }} --}}
                        </th>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- {{ $item['inventory_number'] }} --}}
                        </td>
                        <td class="px-6 py-4 break-words whitespace-normal align-top text-left">
                            {{-- {{ $item['type'] }} --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- {{ $item['legal_entity'] }} --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- {{ $item['date_creation'] }} --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- @if ($item['status'] === 'APPROVED') --}}
                            {{-- @elseif ($item['status'] === 'NEW')
                                <span class="badge-red">{{ __('equipment.non_active') }}</span>
                            @elseif ($item['status'] === 'DISMISSED')
                                <span class="badge-yellow">{{ __('forms.draft') }}</span>
                            @elseif ($item['status'] === 'VERIFIED')
                                <span class="badge-red">{{ __('equipment.marked_as_incorrect') }}</span>
                            @endif --}}
                        </td>
                        <td class="px-6 py-4 align-top text-left">
                            {{-- @if ($item['availability'] === 'AVAILABLE') --}}
                            {{-- @elseif ($item['availability'] === 'DAMAGED')
                                <span class="badge-red">{{ __('equipment.damaged') }}</span>
                            @elseif ($item['availability'] === 'DESTROYED')
                                <span class="badge-red">{{ __('equipment.destroyed') }}</span>
                            @elseif ($item['availability'] === 'LOST')
                                <span class="badge-red">{{ __('equipment.lost') }}</span>
                            @endif --}}
                        </td>
                        <td class="px-6 py-4 text-center align-top">
                            <a href="#"
                               class="inline-flex items-center justify-center w-full text-sm text-gray-600 hover:text-blue-600 transition-colors"
                               title="{{ __('forms.edit') }}">
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
                {{--{{ ->links() }}--}}
            </div>
        </div>
    </div>

</div>
