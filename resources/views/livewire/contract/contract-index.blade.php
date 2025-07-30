<div class="min-h-screen dark:bg-gray-800">
    <x-section-navigation x-data="{ showFilter: false }">
        <x-slot name="title">
            {{ __('forms.contract.contracts') }}
        </x-slot>
    </x-section-navigation>
    <div class="flex flex-wrap items-start justify-between gap-4 mb-18">
        <div class="w-96">
            <x-forms.form-group>
                <x-slot name="label">
                    <label for="contract_type_filter" class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
                        <span>{{ __('forms.contract.show') }}</span>
                    </label>
                </x-slot>
                <x-slot name="input">
                    <div class="form-group group w-full relative" x-data="{ open: false, selectedTypes: @entangle('contractType').live }">
                        <input type="text"
                               id="contract_type_filter"
                               class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                               placeholder="Оберіть тип"
                               x-on:click="open = !open"
                               :value="selectedTypes.length ? selectedTypes.map(type => {
                            if (type === 'APPLICATIONS') return 'Заявки на договір';
                            if (type === 'CONTRACTS') return 'Договори';
                            return type;
                        }).join(', ') : ''"
                               readonly
                               autocomplete="off"
                        />
                        <svg class="w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none z-10" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
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
                             class="absolute z-20 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg top-full"
                        >
                            <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                <li>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" value="APPLICATIONS" {{--wire:model.live="contractType"--}}
                                        class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                        <span>Заявки на договір</span>
                                    </label>
                                </li>
                                <li>
                                    <label class="flex items-center space-x-2 cursor-pointer">
                                        <input type="checkbox" value="CONTRACTS" {{--wire:model.live="contractType"--}}
                                        class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent" />
                                        <span>Договори</span>
                                    </label>
                                </li>
                            </ul>
                        </div>
                    </div>
                </x-slot>
            </x-forms.form-group>
        </div>
        <div class="flex items-center space-x-2 mt-8">
            <a {{--href="{{ route('employee-request.create', ['legalEntity' => legalEntity()->id]) }}"--}}
               class="button-primary">{{ __('forms.contract.new_contract') }}</a>
            <button {{--wire:click="sync"--}} type="button" class="button-sync">
                {{ __('forms.synchronise_with_eHealth') }}
            </button>
        </div>
    </div>
    <div class="mt-6">
        <table class="table-input w-full table-fixed">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input w-[28%]">{{ __('forms.contract.number') }}</th>
                <th scope="col" class="th-input w-[22%]">{{ __('forms.contract.startDateContract') }}</th>
                <th scope="col" class="th-input w-[20%]">{{ __('forms.contract.endDateContract') }}</th>
                <th scope="col" class="th-input w-[15%]">{{ __('forms.contract.status') }}</th>
                <th scope="col" class="th-input w-[15%] text-center"></th>
            </tr>
            </thead>
        </table>
    </div>
</div>
