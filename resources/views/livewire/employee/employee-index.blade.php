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
                        {{-- Division Filter - Commented out --}}
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
                        </x-forms.form-group>
                    </div>
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


                                                <div x-show="open" x-transition class="absolute right-0 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600" style="display: none;">
                                                    <ul class="py-1 text-sm text-gray-700 dark:text-gray-200" @click="open = false">
                                                        <li><a href="{{ route('employee.show', ['legalEntity' => legalEntity()->id, 'id' => $position->id]) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">Переглянути</a></li>
                                                        <li><a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'employeeId' => $position->id]) }}" class="block py-2 px-4 hover:bg-gray-100 dark:hover:bg-gray-600">Редагувати</a></li>
                                                    </ul>
                                                    @if($position->type === 'employee' && $position->status === \App\Enums\Status::APPROVED)
                                                        <div class="py-1" @click="open = false">
                                                            <button type="button" wire:click="showModalDismissed({{ $position->id }})" class="block w-full text-left py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600">
                                                                Звільнити
                                                            </button>
                                                        </div>
                                                    @endif
                                                    @if($position->type === 'request' && !$position->uuid)
                                                        <div class="py-1" @click="open = false">
                                                            <button type="button" wire:click="confirmRequestDeletion({{ $position->id }})" class="block w-full text-left py-2 px-4 text-sm text-red-600 hover:bg-gray-100 dark:hover:bg-gray-600 dark:text-gray-200 dark:hover:text-white">
                                                                Видалити чернетку
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
                    </fieldset>
                @empty
                    <div class="text-center py-16">
                        <p class="text-gray-500 dark:text-gray-400 text-lg">{{__('Нічого не знайдено')}}</p>
                    </div>
                @endforelse
            </div>
            </div>
        </x-section>

    {{-- MODAL FOR DISMISSAL --}}
    <div x-data="{ showDismissModal: @entangle('showModal') }">
        <template x-teleport="body">
            <div x-show="showDismissModal" style="display: none" @keydown.escape.prevent.stop="showDismissModal = false" role="dialog" aria-modal="true" class="fixed inset-0 z-50 overflow-y-auto">
                {{-- Overlay --}}
                <div x-show="showDismissModal" x-transition.opacity class="fixed inset-0 bg-black/30"></div>

                {{-- Panel with outer border --}}
                <div x-show="showDismissModal" x-transition @click="showDismissModal = false" class="relative flex min-h-screen items-center justify-center p-4">
                    <div @click.stop x-trap.noscroll.inert="showDismissModal" class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">

                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            <span x-text="$wire.dismissal_employee_name || 'Підтвердження дії'"></span> - звільнення
                        </h2>

                        <p class="mt-4 text-sm text-gray-600 whitespace-pre-line dark:text-gray-300" x-text="$wire.dismiss_text"></p>

                        {{-- Buttons aligned to match the design --}}
                        <div class="mt-6 flex justify-center gap-4">
                            {{-- Blue "Cancel" button on the left --}}
                            <button
                                type="button"
                                @click="showDismissModal = false"
                                wire:click="closeModal"
                                class="button-primary">  {{-- Changed to primary for blue color --}}
                                Скасувати
                            </button>
                            {{-- Red "Dismiss" button on the right --}}
                            <button
                                type="button"
                                wire:click="deleteRequest"
                                wire:loading.attr="disabled"
                                class="button-danger">
                                Звільнити
                            </button>
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
                {{-- Overlay --}}
                <div x-show="show" x-transition.opacity class="fixed inset-0 bg-black/30"></div>

                {{-- Panel with outer border --}}
                <div x-show="show" x-transition @click="show = false" class="relative flex min-h-screen items-center justify-center p-4">
                    <div @click.stop x-trap.noscroll.inert="show" class="relative w-full max-w-md overflow-hidden rounded-lg bg-white p-6 text-center shadow-lg border border-gray-200 dark:border-gray-700 dark:bg-gray-800">

                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            Видалення чернетки для <span x-text="$wire.deleteRequestName || 'співробітника'"></span>
                        </h3>

                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400" x-text="$wire.deleteRequestText"></p>

                        {{-- Buttons aligned to match the design --}}
                        <div class="mt-6 flex justify-center gap-4">
                            {{-- Blue "Cancel" button on the left --}}
                            <button
                                type="button"
                                @click="show = false"
                                class="button-primary"> {{-- Changed to primary for blue color --}}
                                Скасувати
                            </button>
                            {{-- Red "Delete" button on the right --}}
                            <button
                                type="button"
                                wire:click="deleteRequest"
                                wire:loading.attr="disabled"
                                class="inline-flex justify-center rounded-lg border border-transparent bg-red-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Видалити
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</body>
