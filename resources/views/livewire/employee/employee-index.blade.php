<body class="bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400">
<div>
    <x-section-navigation x-data="{ showFilter: false }">

        <x-slot name="title">
            {{ __('forms.employees') }}
        </x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col">
                <div class="flex flex-wrap items-end justify-between gap-4"> <div class="flex items-end gap-4"> <div class="w-96">
                            <x-forms.form-group>
                                <x-slot name="label">
                                    <label for="employee_search" class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
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
                                        </svg>
                                        <span>{{ __('forms.employeeSearch') }}</span>
                                    </label>
                                </x-slot>
                                <x-slot name="input">
                                    <x-forms.input class="default-input" wire:model.live.debounce.300ms="search"
                                                   type="text" id="employee_search" placeholder="{{ __('forms.full_name') }}" autocomplete="off" />
                                </x-slot>
                            </x-forms.form-group>
                        </div>

                        <button class="flex items-center gap-2 gray-button relative top-[8px]" @click="showFilter = !showFilter">
                            <svg width="16" height="16" id="svg-adjustments" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M4.00003 3.1999C4.00003 2.98773 3.91574 2.78425 3.76571 2.63422C3.61568 2.48419 3.4122 2.3999 3.20003 2.3999C2.98785 2.3999 2.78437 2.48419 2.63434 2.63422C2.48431 2.78425 2.40003 2.98773 2.40003 3.1999V9.0143C2.15682 9.15474 1.95485 9.35672 1.81444 9.59994C1.67402 9.84316 1.6001 10.1191 1.6001 10.3999C1.6001 10.6807 1.67402 10.9566 1.81444 11.1999C1.95485 11.4431 2.15682 11.6451 2.40003 11.7855V12.7999C2.40003 13.0121 2.48431 13.2156 2.63434 13.3656C2.78437 13.5156 2.98785 13.5999 3.20003 13.5999C3.4122 13.5999 3.61568 13.5156 3.76571 13.3656C3.91574 13.2156 4.00003 13.0121 4.00003 12.7999V11.7855C4.24324 11.6451 4.4452 11.4431 4.58562 11.1999C4.72603 10.9566 4.79996 10.6807 4.79996 10.3999C4.79996 10.1191 4.72603 9.84316 4.58562 9.59994C4.4452 9.35672 4.24324 9.15474 4.00003 9.0143V3.1999ZM8.80003 3.1999C8.80003 2.98773 8.71574 2.78425 8.56571 2.63422C8.41568 2.48419 8.2122 2.3999 8.00003 2.3999C7.78785 2.3999 7.58437 2.48419 7.43434 2.63422C7.28431 2.78425 7.20003 2.98773 7.20003 3.1999V4.2143C6.95682 4.35474 6.75485 4.55672 6.61444 4.79994C6.47402 5.04316 6.4001 5.31906 6.4001 5.5999C6.4001 5.88075 6.47402 6.15665 6.61444 6.39987C6.75485 6.64309 6.95682 6.84507 7.20003 6.9855V12.7999C7.20003 13.0121 7.28431 13.2156 7.43434 13.3656C7.58437 13.5156 7.78785 13.5999 8.00003 13.5999C8.2122 13.5999 8.41568 13.5156 8.56571 13.3656C8.71574 13.2156 8.80003 13.0121 8.80003 12.7999V6.9855C9.04324 6.84507 9.2452 6.64309 9.38562 6.39987C9.52603 6.15665 9.59996 5.88075 9.59996 5.5999C9.59996 5.31906 9.52603 5.04316 9.38562 4.79994C9.2452 4.55672 9.04324 4.35474 8.80003 4.2143V3.1999ZM12.8 2.3999C13.0122 2.3999 13.2157 2.48419 13.3657 2.63422C13.5157 2.78425 13.6 2.98773 13.6 3.1999V9.0143C13.8432 9.15474 14.0452 9.35672 14.1856 9.59994C14.326 9.84316 14.4 10.1191 14.4 10.3999C14.4 10.6807 14.326 10.9566 14.1856 11.1999C14.0452 11.4431 13.8432 11.6451 13.6 11.7855V12.7999C13.6 13.0121 13.5157 13.2156 13.3657 13.3656C13.2157 13.5156 13.0122 13.5999 12.8 13.5999C12.5879 13.5999 12.3844 13.5156 12.2343 13.3656C12.0843 13.2156 12 13.0121 12 12.7999V11.7855C11.7568 11.6451 11.5549 11.4431 11.4144 11.1999C11.274 10.9566 11.2001 10.6807 11.2001 10.3999C11.2001 10.1191 11.274 9.84316 11.4144 9.59994C11.5549 9.35672 11.7568 9.15474 12 9.0143V3.1999C12 2.98773 12.0843 2.78425 12.2343 2.63422C12.3844 2.48419 12.5879 2.3999 12.8 2.3999V2.3999Z"
                                    fill="currentColor"/>
                            </svg>
                            <span>{{ __('patients.additional_search_parameters') }}</span>
                        </button>
                    </div>

                    <div class="flex items-center space-x-2 pt-5">
                        <a href="{{ route('employee.create', ['legalEntity' => legalEntity()->id]) }}" class="button-primary">
                            {{ __('forms.newEmployee') }}
                        </a>
                        <button wire:click="syncEmployees" type="button" class="button-sync">
                            {{ __('forms.synchronise_with_eHealth') }}
                        </button>
                    </div>
                </div>

                <div x-show="showFilter" x-transition class="pt-4 mt-4 border-t border-gray-200 dark:border-gray-700" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <x-forms.form-group>
                            <x-slot name="label"><label for="filter_phone" class="default-label">Телефон</label></x-slot>
                            <x-slot name="input"><x-forms.input id="filter_phone" class="default-input" type="text" wire:model.live.debounce.300ms="filter.phone" /></x-slot>
                        </x-forms.form-group>
                        <x-forms.form-group>
                            <x-slot name="label"><label for="filter_email" class="default-label">Email</label></x-slot>
                            <x-slot name="input"><x-forms.input id="filter_email" class="default-input" type="email" wire:model.live.debounce.300ms="filter.email" /></x-slot>
                        </x-forms.form-group>
                        <x-forms.form-group>
                            <x-slot name="label"><label for="filter_role" class="default-label">Роль працівника</label></x-slot>
                            <x-slot name="input">
                                <x-forms.select id="filter_role" class="default-input" wire:model.live="filter.role">
                                    <x-slot name="option">
                                        <option value="">Всі ролі</option>
                                        @foreach($dictionaries['EMPLOYEE_TYPE'] ?? [] as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </x-slot>
                                </x-forms.select>
                            </x-slot>
                        </x-forms.form-group>
                        <x-forms.form-group>
                            <x-slot name="label"><label for="filter_position" class="default-label">Посада</label></x-slot>
                            <x-slot name="input">
                                <x-forms.select id="filter_position" class="default-input" wire:model.live="filter.position">
                                    <x-slot name="option">
                                        <option value="">Всі посади</option>
                                        @foreach($dictionaries['POSITION'] ?? [] as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </x-slot>
                                </x-forms.select>
                            </x-slot>
                        </x-forms.form-group>

                        {{-- Division Filter - Commented out as requested --}}
                        {{--
                        <x-forms.form-group>
                            <x-slot name="label"><label for="filter_division" class="default-label">Підрозділ</label></x-slot>
                            <x-slot name="input">
                                 <x-forms.select id="filter_division" class="default-input" wire:model.live="filter.division_id">
                                    <x-slot name="option">
                                        <option value="">Всі підрозділи</option>
                                        @foreach($divisions ?? [] as $division)
                                            <option value="{{ $division->id }}">{{ $division->name }}</option>
                                        @endforeach
                                    </x-slot>
                                </x-forms.select>
                            </x-slot>
                        </x-forms.form-group>
                        --}}

                        <x-forms.form-group>
                            <x-slot name="label"><label for="filter_status" class="default-label">Статус у системі</label></x-slot>
                            <x-slot name="input">
                                <x-forms.select id="filter_status" class="default-input" wire:model.live="status">
                                    <x-slot name="option">
                                        <option value="">Всі статуси</option>
                                        <option value="APPROVED">Активний</option>
                                        <option value="DISMISSED">Звільнений</option>
                                    </x-slot>
                                </x-forms.select>
                            </x-slot>
                        </x-forms.form-group>
                    </div>
                    <div class="flex justify-end mt-4">
                        <button type="button" wire:click="resetFilters" class="text-sm text-blue-600 hover:underline">Скинути всі фільтри</button>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-section-navigation>

    <x-section>
        <div class="space-y-6">
            @forelse($parties as $party)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6" wire:key="party-{{ $party->id }}">
                    <div class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">{{ $party->fullName }}</h3>
                            <div class="flex items-center flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500 mt-2">
                                @if ($mobilePhone = $party->phones->firstWhere('type', 'MOBILE'))
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path></svg>
                                        <a href="tel:{{ $mobilePhone->number }}" class="hover:underline">{{ $mobilePhone->number }}</a>
                                    </span>
                                @endif
                                @if($party->email)
                                    <span class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                <path d="M2.038 5.61A2.01 2.01 0 0 0 2 6v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V6c0-.12-.01-.238-.03-.352l-.866.65-7.89 6.032a2 2 0 0 1-2.429 0L2.884 6.288l-.846-.677Z"/>
                <path d="M20.677 4.117A1.996 1.996 0 0 0 20 4H4c-.225 0-.44.037-.642.105l.758.607L12 10.742 19.9 4.7l.777-.583Z"/>
            </svg><a href="mailto:{{$party->email}}" class="hover:underline">{{ $party->email }}</a>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <button type="button" wire:click="addPhone" class="item-add">
                                <span>{{__('forms.addPosition')}}</span>
                            </button>
                        </div>
                    </div>

                    <div class="flow-root mt-4">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">{{ __('forms.position') }}</th>
                                <th class="px-4 py-3">{{ __('forms.role') }}</th>
                                <th class="px-4 py-3">{{ __('forms.division') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('forms.actions') }}</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $positions = $party->employees->merge($party->employeeRequests); @endphp
                            @foreach($positions as $position)
                                <tr class="border-b-4 border-gray-200 dark:border-gray-700">
                                <td class="px-4 py-3 font-medium">{{ $dictionaries['POSITION'][$position->position] ?? $position->position }}</td>
                                    <td class="px-4 py-3">{{ $dictionaries['EMPLOYEE_TYPE'][$position->employee_type] ?? $position->employee_type }}</td>
                                    <td class="px-4 py-3">{{ $position->division->name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                            <button @click="open = !open" class="inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-800 rounded-lg focus:outline-none dark:text-gray-400 dark:hover:text-white" type="button">
                                                <svg class="w-5 h-5" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                            </button>
                                            <div x-show="open" x-transition class="absolute right-0 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600" style="display: none;">
                                                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                                                    <li><a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'id' => $position->id]) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">Переглянути</a></li>
                                                    <li><a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'employeeId' => $position->id]) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">Редагувати</a></li>
                                                </ul>
                                                @if($position->type === 'employee' && $position->status === \App\Enums\Status::APPROVED)
                                                    <div class="py-1" @click="open = false">
                                                        <button type="button" wire:click="showModalDismissed({{ $position->id }})" class="block w-full text-right py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                            {{ __('forms.dismissed') }}
                                                        </button>
                                                    </div>
                                                @endif
                                                @if($position->type === 'request' && !$position->uuid)
                                                    <div class="py-1" @click="open = false">
                                                        <button type="button" wire:click="confirmRequestDeletion({{ $position->id }})" class="block w-full text-right py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                            {{ __('forms.delete') }}
                                                        </button>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="text-center py-16">
                    <p class="text-gray-500 dark:text-gray-400 text-lg">{{__('Нічого не знайдено')}}</p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $parties->links() }}
        </div>
    </x-section>

    {{-- MODAL FOR DISMISSAL --}}
    <div x-data="{ showDismissModal: @entangle('showModal') }">
        <template x-teleport="body">
            <div x-show="showDismissModal" style="display: none" @keydown.escape.prevent.stop="showDismissModal = false" role="dialog" aria-modal="true" class="fixed inset-0 z-50 overflow-y-auto">
                <div x-show="showDismissModal" x-transition.opacity class="fixed inset-0 bg-black/30"></div>
                <div x-show="showDismissModal" x-transition @click="showDismissModal = false" class="relative flex min-h-screen items-center justify-center p-4">
                    <div @click.stop x-trap.noscroll.inert="showDismissModal" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            <span x-text="$wire.dismissal_employee_name || 'Підтвердження дії'"></span> - звільнення
                        </h2>
                        <p class="mt-4 text-sm text-gray-600 whitespace-pre-line dark:text-gray-300" x-text="$wire.dismiss_text"></p>
                        <div class="mt-6 flex justify-center gap-4">
                            <button type="button" @click="showDismissModal = false" wire:click="closeModal" class="button-primary">Скасувати</button>
                            <button type="button" wire:click="dismissed({{ $dismissed_id }})" wire:loading.attr="disabled" class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Звільнити</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- MODAL FOR DELETING A DRAFT --}}
    <div x-data="{ show: @entangle('showDeleteModal') }">
        <template x-teleport="body">
            <div x-show="show" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true" style="display: none;">
                <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/30"></div>
                <div x-show="show" x-transition @click="show = false" class="relative flex min-h-screen items-center justify-center p-4">
                    <div @click.stop x-trap.noscroll.inert="show" class="relative w-full max-w-md overflow-hidden rounded-lg bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Видалення чернетки для <span x-text="$wire.deleteRequestName || 'співробітника'"></span>
                        </h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-text="$wire.deleteRequestText"></p>
                        <div class="mt-6 flex justify-center gap-4">
                            <button type="button" @click="show = false" class="button-primary">Скасувати</button>
                            <button type="button" wire:click="deleteRequest" wire:loading.attr="disabled" class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">Видалити</button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>
</body>
