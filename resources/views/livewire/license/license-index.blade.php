@use('App\Enums\License\Type')
@use('Carbon\CarbonImmutable')

<div>
    <x-header-navigation x-data="{ showFilter: false }" title="{{ __('forms.licenses') }}">
        <div class="flex flex-col">
            <div class="flex flex-wrap items-end justify-between gap-4 max-w-6xl">
                <div class="flex items-end gap-4"></div>
                <div class="ml-auto flex items-center gap-6 self-start -mt-9 translate-x-6">
                    @can('create', $licenses)
                        <a href="{{ route('license.create', [legalEntity()]) }}"
                           class="button-primary flex items-center gap-2"
                        >
                            @icon('plus', 'w-4 h-4')
                            {{ __('licenses.create') }}
                        </a>
                    @endcan
                    <button wire:click="sync" class="button-sync flex items-center gap-2">
                        @icon('refresh', 'w-4 h-4')
                        {{ __('forms.synchronise_data_with_EHealth') }}
                    </button>
                </div>
            </div>
        </div>
    </x-header-navigation>

    <div class="max-w-7xl mx-auto">
        <table class="table-input w-full">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input">{{__('licenses.type')}}</th>
                <th scope="col" class="th-input">{{__('licenses.active_from_date_label')}}</th>
                <th scope="col" class="th-input">{{__('licenses.expiry_date_label')}}</th>
                <th scope="col" class="th-input">{{__('licenses.activity')}}</th>
                <th scope="col" class="th-input">{{__('licenses.kind')}}</th>
                <th scope="col" class="th-input">{{__('forms.action')}}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($licenses as $license)
                <tr>
                    <td class="td-input">{{ $license->type->label() }}</td>
                    <td class="td-input">{{ CarbonImmutable::parse($license->activeFromDate)->format('d.m.Y') }}</td>
                    <td class="td-input">{{ CarbonImmutable::parse($license->expiryDate)->format('d.m.Y') }}</td>
                    <td class="td-input">{{ $license->whatLicensed }}</td>
                    <td class="td-input">
                        @if($license->isPrimary)
                            <span class="badge-green">{{ __('licenses.primary') }}</span>
                        @else
                            <span class="badge-yellow">{{ __('licenses.not_primary') }}</span>
                        @endif
                    </td>
                    <td class="td-input text-center">
                        @if($license->isPrimary)
                            <a href="{{ route('license.view', [legalEntity(), $license->id]) }}"
                               class="text-gray-800 dark:text-gray-200 hover:text-black dark:hover:text-white"
                               title="{{ __('forms.view') }}"
                            >
                                @icon('eye', 'w-5 h-5 svg-hover-action')
                            </a>
                        @else
                            <div x-data="{ open: false }" class="relative inline-block text-left">
                                <button @click="open = !open"
                                        @click.outside="open = false"
                                        class="cursor-pointer text-gray-500 hover:text-gray-800 dark:hover:text-white focus:outline-none"
                                >
                                    <svg class="w-5 h-5" aria-hidden="true" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>

                                <div x-show="open"
                                     x-transition
                                     class="absolute right-0 z-10 mt-2 w-40 origin-top-right rounded-md bg-white dark:bg-gray-700 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                                >
                                    <div class="py-1">
                                        <a href="{{ route('license.view', [legalEntity(), $license->id]) }}"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            {{ __('forms.view') }}
                                        </a>
                                        <a href="{{ route('license.view', [legalEntity(), $license->id]) }}"
                                           class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-600"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5M18.5 2.5a2.121 2.121 0 113 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                            </svg>
                                            {{ __('forms.update') }}
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
        {{ $licenses->links() }}
    </div>

    <x-messages/>
    <x-forms.loading/>
</div>
