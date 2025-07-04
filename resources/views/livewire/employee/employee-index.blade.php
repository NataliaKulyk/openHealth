<body class="bg-white dark:bg-gray-800">
<div>
    <x-section-navigation x-data="{ showFilter: false }">

        <x-slot name="title">
            {{ __('forms.employees') }}
        </x-slot>

        <x-slot name="navigation">
            <label for="employee_search" class="text-sm font-medium text-gray-900 dark:text-white block mb-2">
                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400"
                     aria-hidden="true"
                     xmlns="http://www.w3.org/2000/svg"
                     fill="none"
                     viewBox="0 0 20 20"
                >
                    <path stroke="currentColor"
                          stroke-linecap="round"
                          stroke-linejoin="round"
                          stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                </svg>{{ __('forms.employeeSearch') }}
            </label>
            <div class="justify-between block sm:flex md:divide-x md:divide-gray-100 dark:divide-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-3 sm:gap-4">
                    <x-forms.form-group>
                        <x-slot name="input" class="max-w-2xs">
                            <div x-data="{ showDropdown: false }" class="relative w-48 mt-1 sm:w-64 xl:w-96">

                                <x-forms.input class="default-input" wire:model.live="employee_filter.full_name"
                                               type="text" x-on:keyup="showDropdown = true"
                                               x-on:keydown.escape="showDropdown = false" x-on:click.away="showDropdown = false"
                                               id="employee_name" placeholder="{{ __('forms.full_name') }}" autocomplete="off" />
                                <x-dropdown-list x-show="showDropdown" class="absolute z-10">
                                    <x-slot name="lists">
                                        @if ($employees && count($employees) > 0)
                                            @foreach ($employees as $employee)
                                                <li class="mb-3 cursor-pointer"
                                                    x-on:click.prevent="
                                                        $wire.set('employee_filter.employee_uuid', '{{ $employee['uuid'] }}');
                                                        $wire.set('employee_filter.full_name', '{{ $employee->fullName }}');
                                                        showDropdown = false;
                                                    ">
                                                    {{ $employee->fullName }}
                                                </li>
                                            @endforeach
                                        @endif
                                    </x-slot>
                                </x-dropdown-list>
                            </div>
                        </x-slot>
                    </x-forms.form-group>
                    <div class="flex items-center mb-4 sm:mb-0">
                        <div class="flex items-center w-full sm:justify-end">
                            <div class="flex pl-2 space-x-1">
                                <button class="flex items-center relative top-[4px] gap-2 gray-button"
                                        @click.prevent="showFilter = !showFilter">
                                    <svg width="16" height="16" id="svg-adjustments" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path
                                            d="M4.00003 3.1999C4.00003 2.98773 3.91574 2.78425 3.76571 2.63422C3.61568 2.48419 3.4122 2.3999 3.20003 2.3999C2.98785 2.3999 2.78437 2.48419 2.63434 2.63422C2.48431 2.78425 2.40003 2.98773 2.40003 3.1999V9.0143C2.15682 9.15474 1.95485 9.35672 1.81444 9.59994C1.67402 9.84316 1.6001 10.1191 1.6001 10.3999C1.6001 10.6807 1.67402 10.9566 1.81444 11.1999C1.95485 11.4431 2.15682 11.6451 2.40003 11.7855V12.7999C2.40003 13.0121 2.48431 13.2156 2.63434 13.3656C2.78437 13.5156 2.98785 13.5999 3.20003 13.5999C3.4122 13.5999 3.61568 13.5156 3.76571 13.3656C3.91574 13.2156 4.00003 13.0121 4.00003 12.7999V11.7855C4.24324 11.6451 4.4452 11.4431 4.58562 11.1999C4.72603 10.9566 4.79996 10.6807 4.79996 10.3999C4.79996 10.1191 4.72603 9.84316 4.58562 9.59994C4.4452 9.35672 4.24324 9.15474 4.00003 9.0143V3.1999ZM8.80003 3.1999C8.80003 2.98773 8.71574 2.78425 8.56571 2.63422C8.41568 2.48419 8.2122 2.3999 8.00003 2.3999C7.78785 2.3999 7.58437 2.48419 7.43434 2.63422C7.28431 2.78425 7.20003 2.98773 7.20003 3.1999V4.2143C6.95682 4.35474 6.75485 4.55672 6.61444 4.79994C6.47402 5.04316 6.4001 5.31906 6.4001 5.5999C6.4001 5.88075 6.47402 6.15665 6.61444 6.39987C6.75485 6.64309 6.95682 6.84507 7.20003 6.9855V12.7999C7.20003 13.0121 7.28431 13.2156 7.43434 13.3656C7.58437 13.5156 7.78785 13.5999 8.00003 13.5999C8.2122 13.5999 8.41568 13.5156 8.56571 13.3656C8.71574 13.2156 8.80003 13.0121 8.80003 12.7999V6.9855C9.04324 6.84507 9.2452 6.64309 9.38562 6.39987C9.52603 6.15665 9.59996 5.88075 9.59996 5.5999C9.59996 5.31906 9.52603 5.04316 9.38562 4.79994C9.2452 4.55672 9.04324 4.35474 8.80003 4.2143V3.1999ZM12.8 2.3999C13.0122 2.3999 13.2157 2.48419 13.3657 2.63422C13.5157 2.78425 13.6 2.98773 13.6 3.1999V9.0143C13.8432 9.15474 14.0452 9.35672 14.1856 9.59994C14.326 9.84316 14.4 10.1191 14.4 10.3999C14.4 10.6807 14.326 10.9566 14.1856 11.1999C14.0452 11.4431 13.8432 11.6451 13.6 11.7855V12.7999C13.6 13.0121 13.5157 13.2156 13.3657 13.3656C13.2157 13.5156 13.0122 13.5999 12.8 13.5999C12.5879 13.5999 12.3844 13.5156 12.2343 13.3656C12.0843 13.2156 12 13.0121 12 12.7999V11.7855C11.7568 11.6451 11.5549 11.4431 11.4144 11.1999C11.274 10.9566 11.2001 10.6807 11.2001 10.3999C11.2001 10.1191 11.274 9.84316 11.4144 9.59994C11.5549 9.35672 11.7568 9.15474 12 9.0143V3.1999C12 2.98773 12.0843 2.78425 12.2343 2.63422C12.3844 2.48419 12.5879 2.3999 12.8 2.3999V2.3999Z"
                                            fill="currentColor"/>
                                    </svg>
                                    <span>{{ __('forms.additional_search_parameters') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="button-group">
                    <a href="{{ route('employee.create', ['legalEntity' => legalEntity()->id]) }}" class="button-primary">
                            {{ __('forms.newEmployee') }}
                        </a>
                    <button wire:click="syncEmployees" type="button" class="button-sync">
                        {{ __('forms.synchronise_with_eHealth') }}
                    </button>
                </div>
            </div>
            <livewire:components.declaration.declarations-filter />

        </x-slot>
    </x-section-navigation>

        <x-section>
            <div class="space-y-6 bg-white dark:bg-gray-800 rounded-md p-4">
            <div class="space-y-6">
                @forelse($parties as $party)
                    <fieldset class="fieldset space-y-4" wire:key="party-{{ $party->id }}">
                        <legend class="legend text-xl font-bold text-gray-900 dark:text-white">{{ $party->fullName }}</legend>

                        <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                            <div>
                                <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 mt-2">
                                    @if ($mobilePhone = $party->phones->firstWhere('type', 'MOBILE'))
                                        <span class="flex items-center gap-1.5">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                                              <path d="M2.003 5.884l3.76-1.608a1 1 0 011.171.343l2.148 2.974a1 1 0 01-.234 1.424l-1.207.805a11.042 11.042 0 005.292 5.292l.805-1.207a1 1 0 011.424-.234l2.974 2.148a1 1 0 01.343 1.171l-1.608 3.76a1 1 0 01-.986.625C7.82 18 2 12.18 2 5.998a1 1 0 01.003-.114z" />
                                          </svg>
                                         <a href="tel:{{ $mobilePhone->number }}" class="hover:underline">{{ $mobilePhone->number }}</a>
                                        </span>
                                    @endif
                                    @if($party->email)
                                        <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                           <path d="M2.038 5.61A2.01 2.01 0 0 0 2 6v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6c0-.12-.01-.238-.03-.352l-.866.65-7.89 6.032a2 2 0 0 1-2.429 0L2.884 6.288l-.846-.677Z"/>
                                           <path d="M20.677 4.117A1.996 1.996 0 0 0 20 4H4c-.225 0-.44.037-.642.105l.758.607L12 10.742 19.9 4.7l.777-.583Z"/>
                                        </svg>
                                        <a href="mailto:{{$party->email}}" class="hover:underline">{{ $party->email }}</a>
                                    </span>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-300 ml-8 sm:ml-12 mt-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">Статус:</span>
                                    <span class="new">Активний</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">Верифікація:</span>
                                    <span class="rejected">не верифіковано</span>
                                </div>
                            </div>

                            <div class="flex items-center space-x-3">
                                <button type="button"
                                        onclick="window.location.href='{{ route('employee.add-position', ['legalEntity' => legalEntity()->id, 'party' => $party->id]) }}'"
                                        class="item-add text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <span class="text-xl leading-none"></span>
                                    <span>{{ __('forms.addPosition') }}</span>
                                </button>

                            </div>
                        </div>

                        <div class="flow-root mt-4">
                            <table class="table-base w-full text-sm text-left">
                                <thead class="table-header">
                                <tr>
                                    <th class="th-input">Посада</th>
                                    <th class="th-input">Роль</th>
                                    <th class="th-input">Підрозділ</th>
                                    <th class="th-input text-right">Дії</th>
                                </tr>
                                </thead>
                                <tbody>
                                @php $positions = $party->employees->merge($party->employeeRequests); @endphp
                                @foreach($positions as $position)
                                    <tr class="border-b-4 border-gray-300 dark:border-gray-600">
                                        <td class="td-input font-medium">{{ $dictionaries['POSITION'][$position->position] ?? $position->position }}</td>
                                        <td class="td-input">{{ $dictionaries['EMPLOYEE_TYPE'][$position->employee_type] ?? $position->employee_type }}</td>
                                        <td class="td-input">{{ $position->division->name ?? 'N/A' }}</td>
                                        <td class="td-input text-right">
                                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                                <button @click="open = !open"
                                                        class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white"
                                                        type="button">
                                                    <svg class="w-6 h-6 text-gray-800 dark:text-gray-200" aria-hidden="true"
                                                         xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                         fill="none" viewBox="0 0 24 24">
                                                        <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2"
                                                              d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                                    </svg>
                                                </button>


                                                <div x-show="open" x-transition
                                                     class="absolute right-0 z-10 w-36 bg-white rounded-md shadow-sm py-1 divide-y divide-gray-100 dark:bg-gray-700 dark:divide-gray-600"
                                                     style="display: none;">
                                                    <ul class="text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                                                        <li>
                                                            <a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'employee' => $position->id]) }}"
                                                               class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                                                                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                                                    <path d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/>
                                                                    <circle cx="12" cy="12" r="3"/>
                                                                </svg>
                                                                Переглянути
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'employeeId' => $position->id]) }}"
                                                               class="flex items-center gap-2 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-300" fill="none" stroke="currentColor"
                                                                     stroke-width="1.5" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                                          d="M16.862 3.487a2.25 2.25 0 013.182 3.182L7.5 19.5 3 21l1.5-4.5 12.362-13.013z"/>
                                                                </svg>
                                                                Редагувати
                                                            </a>
                                                        </li>
                                                        @if($position instanceof \App\Models\Employee\Employee && $position->status === \App\Enums\Status::APPROVED)
                                                            <li>
                                                                <button type="button"
                                                                        wire:click="showModalDismissed({{ $position->id }})"
                                                                        class="flex items-center gap-2 px-3 py-2 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 w-full text-left">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                                         stroke-width="1.5" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                                              d="M6 18L18 6M6 6l12 12"/>
                                                                    </svg>
                                                                    Звільнити з посади
                                                                </button>
                                                            </li>
                                                        @endif
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </fieldset>
                @empty
                    <div class="text-center py-16">
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{__('Нічого не знайдено')}}</p>
                    </div>
                @endforelse
            </div>
            </div>
        </x-section>

        @if ($showModal)
            <x-alert-modal>
                <x-slot name="title">{{ __('forms.dismissed') }}</x-slot>
                <x-slot name="text">{{ $dismiss_text }}</x-slot>
                <x-slot name="button">
                    <div class="justify-between items-center pt-0 space-y-4 sm:flex sm:space-y-0">
                        <button wire:click="closeModal" type="button" class="button-minor">{{__('forms.cancel')}}</button>
                        <button wire:click="dismissed({{$dismissed_id}})" type="button" class="button-danger">{{__('forms.confirm')}}</button>
                    </div>
                </x-slot>
            </x-alert-modal>
        @endif
    </div>
</body>
