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
                <div class="flex items-center mb-4 sm:mb-0">
                    <x-forms.form-group class="sm:pr-3">
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
                                <a href="#"
                                   x-on:click="showFilter = !showFilter"
                                   class="inline-flex justify-center p-1 text-gray-500 rounded cursor-pointer hover:text-gray-900 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                                >
                                    <svg
                                        class="w-6 h-6 text-gray-800 dark:text-white"
                                        aria-hidden="true"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path d="M1 5h1.424a3.228 3.228 0 0 0 6.152 0H19a1 1 0 1 0 0-2H8.576a3.228 3.228 0 0 0-6.152 0H1a1 1 0 1 0 0 2Zm18 4h-1.424a3.228 3.228 0 0 0-6.152 0H1a1 1 0 1 0 0 2h10.424a3.228 3.228 0 0 0 6.152 0H19a1 1 0 0 0 0-2Zm0 6H8.576a3.228 3.228 0 0 0-6.152 0H1a1 1 0 0 0 0 2h1.424a3.228 3.228 0 0 0 6.152 0H19a1 1 0 0 0 0-2Z"/>
                                    </svg>
                                    <span class="ml-1.5 txt-sm:hidden">{{ __('patients.additional_search_parameters') }}</span>
                                </a>
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
                                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 2.25l7.997 3.634A1 1 0 0017 4.584V15.5a1 1 0 00-1.997-.084L10 11.75l-5.003 3.666A1 1 0 003 15.5V4.584a1 1 0 00-1.997 1.25l.004-.001z"></path></svg>
                                        <a href="mailto:{{$party->email}}" class="hover:underline">{{ $party->email }}</a>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            <a href="{{ route('employee.add-position', ['legalEntity' => legalEntity()->id, 'party' => $party->id]) }}" class="button-secondary-outline">
                                {{ __('forms.addPosition') }}
                            </a>
                        </div>
                    </div>

                    <div class="flow-root mt-4">
                        <table class="w-full text-sm text-left">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Посада</th>
                                <th class="px-4 py-3">Роль</th>
                                <th class="px-4 py-3">Підрозділ</th>
                                <th class="px-4 py-3 text-right">Дії</th>
                            </tr>
                            </thead>
                            <tbody>
                            @php $positions = $party->employees->merge($party->employeeRequests); @endphp
                            @foreach($positions as $position)
                                <tr class="border-b dark:border-gray-700">
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
                                                    <li><a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'employee' => $position->id]) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">Переглянути</a></li>
                                                    <li><a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'employeeId' => $position->id]) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">Редагувати</a></li>
                                                </ul>
                                                @if($position instanceof \App\Models\Employee\Employee && $position->status === \App\Enums\Status::APPROVED)
                                                    <div class="py-1" @click="open = false">
                                                        <button type="button" wire:click="showModalDismissed({{ $position->id }})" class="block w-full text-left py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">Звільнити</button>
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
    </x-section>

    {{-- The modal for dismissal --}}
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
