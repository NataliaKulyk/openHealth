@use('App\Enums\Status')

@php
    // Table headers are now defined directly in the <thead> below for precise width control
    $tableHeaders = [
            __('healthcare-services.specialisation'),
            __('forms.division_name'),
            __('healthcare-services.providing_condition'),
            __('healthcare-services.created_at'),
            __('healthcare-services.status'),
            __('forms.action')
        ];
@endphp

<div>
    <x-messages/>
    <x-forms.loading/>

    <x-header-navigation x-data="{ showFilter: false }">
        <x-slot name="title">{{ __('forms.services') }}</x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col">
                <div class="flex flex-wrap items-end justify-between gap-4 max-w-6xl">
                    <div class="flex items-end gap-4"></div>

                    <div class="ml-auto flex items-center gap-6 self-start -mt-22 translate-x-10">
                        @isset($divisionId)
                            <a href="{{ route('healthcare-service.create', [legalEntity(), $divisionId]) }}"
                               class="button-primary flex items-center gap-2">
                                @icon('plus', 'w-4 h-4')
                                {{ __('forms.add_healthcare_service') }}
                            </a>
                        @endisset

                        <button wire:click="sync" class="button-sync flex items-center gap-2">
                            @icon('refresh', 'w-4 h-4')
                            {{ __('forms.synchronise_with_eHealth') }}
                        </button>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-header-navigation>

    <div class="flow-root mt-8 shift-content pl-3.5">
        <div class="max-w-screen-xl">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full min-w-[1100px] table-fixed text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 w-[24%]">{{ __('healthcare-services.specialisation') }}</th>
                        <th class="px-6 py-3 w-[24%]">{{ __('forms.division_name') }}</th>
                        <th class="px-6 py-3 w-[18%]">{{ __('healthcare-services.providing_condition') }}</th>
                        <th class="px-6 py-3 w-[14%] whitespace-nowrap">{{ __('healthcare-services.created_at') }}</th>
                        <th class="px-6 py-3 w-[14%]">{{ __('healthcare-services.status') }}</th>
                        <th class="px-6 py-3 w-[6%]  whitespace-nowrap">{{ __('forms.action') }}</th>
                    </tr>
                    </thead>

                    <tbody>
                    @nonempty($healthcareServices->items())
                    @foreach ($healthcareServices as $service)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                <p class="font-semibold text-gray-900 dark:text-white">
                                    {{ $dictionaries['SPECIALITY_TYPE'][$service->specialityType] }}
                                </p>
                            </td>

                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                <p class="font-medium text-gray-600 dark:text-gray-500">
                                    {{ $service->division->name }}
                                </p>
                            </td>

                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                <p class="font-medium text-gray-600 dark:text-gray-500">
                                    {{ $dictionaries['PROVIDING_CONDITION'][$service->providingCondition] }}
                                </p>
                            </td>

                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                <p class="text-gray-900 dark:text-white">
                                    {{ $service->ehealthInsertedAt?->format('d.m.Y') }}
                                </p>
                            </td>

                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                <span class="{{
                                    match($service->status) {
                                        Status::DRAFT => 'badge-dark',
                                        Status::ACTIVE => 'badge-green',
                                        Status::INACTIVE => 'badge-red',
                                        default => ''
                                    }
                                }}">
                                    {{ $service->status->label() }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if ($divisionStatus)
                                    <div class="flex justify-center relative">
                                        <div x-data="{
                                                 open: false,
                                                 toggle() { this.open ? this.close() : (this.$refs.button.focus(), this.open = true) },
                                                 close(focusAfter) { if (!this.open) return; this.open = false; focusAfter && focusAfter.focus() }
                                             }"
                                             @keydown.escape.prevent.stop="close($refs.button)"
                                             @focusin.window="!$refs.panel.contains($event.target) && close()"
                                             x-id="['dropdown-button']"
                                             class="relative"
                                        >
                                            <button @click="toggle()"
                                                    x-ref="button"
                                                    :aria-expanded="open"
                                                    :aria-controls="$id('dropdown-button')"
                                                    type="button"
                                                    class="hover:text-primary cursor-pointer"
                                            >
                                                <svg class="svg-hover-action w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                                     xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2"
                                                          d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                                </svg>
                                            </button>

                                            <div x-show="open" x-cloak x-ref="panel" x-transition.origin.top.left
                                                 @click.outside="close($refs.button)"
                                                 :id="$id('dropdown-button')"
                                                 class="absolute right-0 mt-2 w-40 rounded-md bg-white shadow-md z-50"
                                            >
                                                @if ($service->status === Status::ACTIVE)
                                                    <button wire:click="edit({{ $service }}); toggle()"
                                                            class="flex items-center gap-2 w-full px-4 py-2.5 text-left text-sm text-gray-600 hover:bg-gray-50">
                                                        @icon('edit', 'w-5 h-5 text-gray-600')
                                                        {{ __('forms.edit') }}
                                                    </button>
                                                    <button wire:click="deactivate('{{ $service->uuid }}'); toggle()"
                                                            class="flex items-center gap-2 w-full last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50">
                                                        @icon('delete', 'w-5 h-5 text-red-600')
                                                        {{ __('forms.deactivate') }}
                                                    </button>
                                                @else
                                                    <button wire:click="activate('{{ $service->uuid }}'); toggle()"
                                                            class="flex items-center gap-2 w-full first-of-type:rounded-t-md last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm text-green-600 hover:bg-green-50">
                                                        @icon('check-circle', 'w-5 h-5 text-green-600')
                                                        {{ __('forms.activate') }}
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @elsenonempty
                    <tr>
                        <td class="text-black w-full p-4 border-gray-200 text-center dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                            colspan="6">
                            <p>{{ __('forms.nothing_found') }}</p>
                        </td>
                    </tr>
                    @endnonempty
                    </tbody>
                </table>
            </div>

            <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
                <x-pagination :pagination="$healthcareServices" class="pagination"/>
            </div>
        </div>
    </div>

    <div class="footer flex flex-start border-stroke px-7 py-2 my-4">
        <x-secondary-button>
            <a href="{{ route('division.index', legalEntity()) }}">
                {{ __('forms.back') }}
            </a>
        </x-secondary-button>
    </div>
</div>
