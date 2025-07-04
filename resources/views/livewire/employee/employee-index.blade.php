<div>
    <x-section-navigation x-data="{ showFilter: false }">

        <x-slot name="title">
            {{ __('forms.employees') }}
        </x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <x-forms.form-group>
                            <x-slot name="label">
                                <label for="employee_search" class="text-sm font-medium text-gray-900 dark:text-white">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 inline-block mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                                    </svg>
                                    {{ __('forms.employeeSearch') }}
                                </label>
                            </x-slot>
                            <x-slot name="input">
                                <x-forms.input class="default-input" wire:model.live.debounce.300ms="search"
                                               type="text" id="employee_search" placeholder="{{ __('forms.full_name') }}" autocomplete="off" />
                            </x-slot>
                        </x-forms.form-group>
                    </div>

                    <div class="flex items-center space-x-2 pt-5">
                        <button type="button" @click="showFilter = !showFilter" class="button-secondary-outline">
                            <svg class="w-5 h-5 mr-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7.75 4H19M7.75 4a2.25 2.25 0 0 1-4.5 0m4.5 0a2.25 2.25 0 0 0-4.5 0M1 4h2.25m15.75 8H1M1 12h10.25m6.75 0a2.25 2.25 0 0 1-4.5 0m4.5 0a2.25 2.25 0 0 0-4.5 0M1 12h.01M19 12h.01M1 20h2.25m15.75 0H1M1 20h10.25m6.75 0a2.25 2.25 0 0 1-4.5 0m4.5 0a2.25 2.25 0 0 0-4.5 0M1 20h.01M19 20h.01"/></svg>
                            Додаткові параметри
                        </button>
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
                                <th class="px-4 py-3">{{ __('forms.position') }}</th>
                                <th class="px-4 py-3">{{ __('forms.role') }}</th>
                                <th class="px-4 py-3">{{ __('forms.division') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('forms.actions') }}</th>
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
