<div>
    <x-header-navigation x-data="{ showFilter: false }" class="" title="{{ __('Ліцензії') }}">
        <div class="flex flex-col">
            <div class="flex flex-wrap items-end justify-between gap-4 max-w-6xl">
                <div class="flex items-end gap-4">
                </div>
                <div class="ml-auto flex items-center gap-2 self-start -mt-9 translate-x-6">
                    <button type="button" class="button-primary">
                        <a href="{{ route('license.create', [legalEntity()]) }}">
                            {{__('forms.license.create')}}
                        </a>
                    </button>
                    <button wire:click="sync" class="button-sync">
                        {{__('forms.synchronise_data_with_EHealth')}}
                    </button>
                </div>
            </div>
        </div>
    </x-header-navigation>

    <div class="max-w-7xl mx-auto">
        <table class="table-input w-full">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input">{{__('forms.license.type')}}</th>
                <th scope="col" class="th-input">{{__('forms.license.active_from_date')}}</th>
                <th scope="col" class="th-input">{{__('forms.license.expiry_date')}}</th>
                <th scope="col" class="th-input">{{__('forms.license.activity')}}</th>
                <th scope="col" class="th-input">{{__('forms.license.kind')}}</th>
                <th scope="col" class="th-input">{{__('forms.license.action')}}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($licensesPagination as $license)
                <tr>
                    <td class="td-input">{{ $dictionaries['LICENSE_TYPE'][$license->type] ?? $license->type }}</td>
                    <td class="td-input">{{ $license->start_date ?? '—' }}</td>
                    <td class="td-input">{{ $license->end_date ?? '—' }}</td>
                    <td class="td-input">{{ $license->what_licensed ?? '—' }}</td>
                    <td class="td-input">
                        @if($license->is_primary)
                            <span class="badge-green">Основна</span>
                        @else
                            <span class="badge-yellow">Додаткова</span>
                        @endif
                    </td>
                    <td class="td-input text-center">
                        @if($license->is_primary)
                            <a href="{{ route('license.view', [legalEntity(), $license->id]) }}"
                               class="text-gray-800 dark:text-gray-200 hover:text-black dark:hover:text-white"
                               title="Переглянути">
                                <svg class="w-5 h-5 svg-hover-action" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M1.5 12s4-7.5 10.5-7.5S22.5 12 22.5 12s-4 7.5-10.5 7.5S1.5 12 1.5 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </a>
                        @else
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button @click="open = !open" @click.outside="open = false"
                                        class="text-gray-500 hover:text-gray-800 dark:hover:text-white focus:outline-none">
                                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>

                                <div x-show="open" x-transition
                                     class="absolute right-0 z-10 mt-2 w-40 origin-top-right rounded-md bg-white dark:bg-gray-700 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                     style="display: none;">
                                    <div class="py-1">
                                        <a href="{{ route('license.view', [legalEntity(), $license->id]) }}"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Переглянути
                                        </a>
                                        <a href="{{ route('license.edit', [legalEntity(), $license->id]) }}"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            Оновити
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">
        <x-pagination :pagination="$licensesPagination" />
    </div>
</div>
