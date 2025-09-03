@use('App\Enums\Declaration\Status')
@use('Carbon\CarbonImmutable')

<div>
    <x-section-navigation x-data="{ showFilter: false }">
        <x-slot name="title">
            {{ __('forms.declarations') }}
        </x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col">
                <div class="flex flex-wrap items-end justify-between gap-4 max-w-6xl mt-8">
                    <div class="flex items-end">
                        <div class="w-96">
                            <div class="flex items-center gap-1">
                                @icon('search-outline', 'w-4 h-4.5 text-gray-800 dark:text-white')
                                <span class="default-p">{{ __('declarations.search_for_declaration') }}</span>
                            </div>

                            {{-- Filter by name --}}
                            <div class="form-group group w-full relative top-3 mt-2">
                                <input type="text"
                                       id="search"
                                       placeholder=" "
                                       class="input peer"
                                       wire:model.live.debounce.300ms="search"
                                       autocomplete="off"
                                />
                                <label for="search" class="label">
                                    {{ __('declarations.patient_full_name') }}
                                </label>
                            </div>

                            {{-- Filter by type --}}
                            <div class="mt-12">
                                <div class="form-group group"
                                     x-data="{ open: false, selectedTypes: $wire.entangle('typeFilter').live }"
                                >
                                    <label for="typeFilter" class="label mb-1">{{ __('declarations.show') }}</label>
                                    <div class="relative">
                                        <input type="text"
                                               id="typeFilter"
                                               class="input peer w-full cursor-pointer text-gray-500 dark:text-gray-400"
                                               @click="open = !open"
                                               :value="selectedTypes.length ? selectedTypes.map(type => type === 'request' ? 'Заявки на декларацію' : (type === 'declaration' ? 'Декларації' : type)).join(', ') : ''"
                                               readonly
                                        />
                                        @icon('chevron-down', 'w-4 h-4 absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-gray-400 pointer-events-none')
                                        <div x-show="open"
                                             @click.away="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95"
                                             class="absolute z-10 mt-2 w-full bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-md shadow-lg"
                                        >
                                            <ul class="py-2 px-3 space-y-2 text-sm text-gray-700 dark:text-gray-200">
                                                <li>
                                                    <label class="flex items-center space-x-2 cursor-pointer">
                                                        <input wire:model.live="typeFilter"
                                                               type="checkbox"
                                                               value="request"
                                                               class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent"
                                                        />
                                                        <span>{{ __('declarations.declaration_requests') }}</span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="flex items-center space-x-2 cursor-pointer">
                                                        <input wire:model.live="typeFilter"
                                                               type="checkbox"
                                                               value="declaration"
                                                               class="rounded-sm text-blue-600 focus:ring-blue-500 border-gray-300 dark:bg-gray-800 dark:border-gray-600 dark:checked:bg-blue-600 dark:checked:border-transparent"
                                                        />
                                                        <span>{{ __('forms.declarations') }}</span>
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ml-auto flex items-center gap-2 self-start -mt-14 translate-x-7">
                        <button wire:click="sync" class="button-sync flex items-center gap-2">
                            @icon('refresh', 'w-4 h-4')
                            {{ __('forms.synchronise_data_with_EHealth') }}
                        </button>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-section-navigation>

    <div class="max-w-7xl mx-auto">
        <table class="table-input w-full">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input">{{__('forms.full_name')}}</th>
                <th scope="col" class="th-input">{{__('forms.number')}}</th>
                <th scope="col" class="th-input">{{__('forms.birth_date_abbreviated')}}</th>
                <th scope="col" class="th-input">{{__('declarations.doctor')}}</th>
                <th scope="col" class="th-input">{{__('forms.status.label')}}</th>
                <th scope="col" class="th-input">{{__('forms.action')}}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($declarations as $declaration)
                <tr>
                    <td class="td-input">{{ $declaration->person->fullName }}</td>
                    <td class="td-input">{{ $declaration->declarationNumber }}</td>
                    <td class="td-input">{{ CarbonImmutable::parse($declaration->person->birth_date)->format('d.m.Y') }}</td>
                    <td class="td-input">{{ $declaration->employee->fullName }}</td>
                    <td class="td-input">
                        <span class="{{
                            match($declaration->status) {
                                Status::DRAFT => 'badge-dark',
                                Status::NEW, Status::APPROVED => 'badge-yellow',
                                Status::ACTIVE => 'badge-green',
                                Status::REJECTED, Status::CANCELLED, Status::TERMINATED => 'badge-red',
                                default => ''
                            }
                        }}">
                            {{ $declaration->status->label() }}
                        </span>
                    </td>
                    <td x-data="{ openDropdown: false }" class="relative td-input text-center overflow-visible">
                        <button @click.stop="openDropdown = !openDropdown" type="button" class="cursor-pointer">
                            @if($declaration->type === 'declaration' || $declaration->status === Status::REJECTED)
                                @icon('eye', 'w-6 h-6 text-gray-800 dark:text-white')
                            @else
                                @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-white')
                            @endif
                        </button>

                        <div x-show="openDropdown"
                             @click.outside="openDropdown = false"
                             x-transition
                             class="absolute right-0 mt-2 z-10 w-fit bg-white rounded divide-y divide-gray-100 shadow"
                             style="display: none"
                        >
                            @if($declaration->type === 'request' && $declaration->status === Status::NEW)
                                @can('approve', $declaration)
                                    <div wire:click="approve({{ $declaration->person->id }}, {{ $declaration->id }})"
                                         @click="openDropdown = false"
                                         class="cursor-pointer text-[#222222] text-nowrap flex gap-3 items-center py-2 pl-4 pr-19"
                                    >
                                        @icon('check-circle', 'w-5 h-5 text-red-500')
                                        {{ __('declarations.approve_declaration') }}
                                    </div>
                                @endcan

                                @can('reject', $declaration)
                                    <div wire:click="reject('{{ $declaration['uuid'] }}')"
                                         @click="openDropdown = false"
                                         class="cursor-pointer text-nowrap text-red-500 flex gap-3 items-center py-2 pl-4 pr-5"
                                    >
                                        @icon('delete', 'w-5 h-5')
                                        {{ __('declarations.reject_declaration_request') }}
                                    </div>
                                @endcan
                            @endif

                            @if($declaration->type === 'request' && $declaration->status === Status::APPROVED)
                                @can('sign', $declaration)
                                    <div wire:click="sign({{ $declaration->person->id }}, {{ $declaration->id }})"
                                         @click="openDropdown = false"
                                         class="cursor-pointer text-[#222222] text-nowrap flex gap-3 items-center py-2 pl-4 pr-19"
                                    >
                                        @icon('check-circle', 'w-5 h-5 text-red-500')
                                        {{ __('declarations.sign_declaration') }}
                                    </div>
                                @endcan

                                @can('reject', $declaration)
                                    <div wire:click="reject('{{ $declaration['uuid'] }}')"
                                         @click="openDropdown = false"
                                         class="cursor-pointer text-nowrap text-red-500 flex gap-3 items-center py-2 pl-4 pr-5"
                                    >
                                        @icon('delete', 'w-5 h-5')
                                        {{ __('declarations.reject_declaration_request') }}
                                    </div>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $declarations->links() }}
    </div>

    <x-forms.loading/>
</div>
