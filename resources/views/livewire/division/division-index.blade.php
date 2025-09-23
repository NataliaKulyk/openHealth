<div
    x-data="{
        divisionId: 0,
        textConfirmation: '',
        actionType: '',
        actionTitle: '',
        actionButtonText: ''
    }"
>
    <x-messages />

    <x-header-navigation x-data="{ showFilter: false }" class="">
        <x-slot name="title">
            {{ __('forms.divisions') }}
        </x-slot>
        <div class="ml-auto flex items-center gap-2 mt-2 lg:mt-0">
            @can('create', App\Models\Division::class)
                <a
                    href="{{ route('division.create', [legalEntity()]) }}"
                    type="button"
                    class="button-primary"
                >
                    {{ __('forms.add_new_division') }}
                </a>
            @endcan

            <button
                wire:click="sync"
                class="button-sync"
            >
                {{ __('forms.synchronise_with_eHealth') }}
            </button>
        </div>
    </x-header-navigation>

    <div class="shift-content flex flex-wrap items-end justify-between gap-4">
        <div class="w-96 ml-3.5">
            <x-forms.form-group>
                <x-slot name="label">
                    <label for="division_search"
                           class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true"
                             xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                  stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                        <span>{{ __('forms.division_search') }}</span>
                    </label>
                </x-slot>
                <x-slot name="input">
                    <div class="form-group group w-full relative mt-3">
                        <input
                            type="text"
                            id="division_search"
                            placeholder=" "
                            class="input peer pb-1"
                            wire:model.live.debounce.300ms="divisionForm.search"
                            autocomplete="off"
                        />

                        <label for="division_search" class="label">Назва</label>
                    </div>
                </x-slot>
            </x-forms.form-group>
        </div>
    </div>

    <div class="flow-root mt-4 shift-content">
        <div class="max-w-screen-xl">
            <table class="table-input w-full table-fixed">

                <thead class="thead-input">
                <tr>
                    <th scope="col" class="th-input text-left w-[20%]">Назва</th>
                    <th scope="col" class="th-input text-left w-[15%]">Тип</th>
                    <th scope="col" class="th-input text-left w-[20%]">Телефон</th>
                    <th scope="col" class="th-input text-left w-[20%]">Email</th>
                    <th scope="col" class="th-input text-left w-[15%]">Статус</th>
                    <th scope="col" class="th-input text-left w-[6%]">Дія</th>
                </tr>
                </thead>

                <tbody>
                @forelse ($divisions as $division)
                    <tr x-data="{ divisionTypes: @entangle('dictionaries.DIVISION_TYPE') }">
                        <td class="td-input break-words whitespace-normal align-top">
                            <p>{{ $division->name ?? '' }}</p>
                        </td>
                        <td x-text="divisionTypes['{{ $division->type }}']" class="td-input break-words whitespace-normal align-top"></td>
                        <td class="td-input break-words whitespace-normal align-top">
                            <p>{{ $division->phones()->first()?->number ?? '' }}</p>
                        </td>
                        <td class="td-input break-words whitespace-normal align-top">
                            <p>{{ $division->email ?? '' }}</p>
                        </td>
                        <td class="td-input break-words whitespace-normal align-top">
                            @if ($division->status ==  \App\Enums\Status::INACTIVE)
                                <span class="badge-red">{{ __('forms.status.non_active') }}</span>
                            @elseif ($division->status ==  \App\Enums\Status::DRAFT)
                                <span class="badge-red">{{ __('forms.status.draft') }}</span>
                            @elseif ($division->status ==  \App\Enums\Status::UNSYNCED)
                                <span class="badge-yellow">{{ __('forms.status.unsynced') }}</span>
                            @else
                                <span class="badge-green">{{ __('forms.status.active') }}</span>
                            @endif
                        </td>
                        <td class="td-input text-center">
                            <div class="flex justify-center relative">
                                <div x-data="{
                                            open: false,
                                            toggle() {
                                                if (this.open) {
                                                    return this.close()
                                                }
                                                this.$refs.button.focus()

                                                this.open = true
                                            },
                                            close(focusAfter) {
                                                if (!this.open) return

                                                this.open = false

                                                focusAfter && focusAfter.focus()
                                            }
                                        }"
                                     x-on:keydown.escape.prevent.stop="close($refs.button)"
                                     x-on:focusin.window="! $refs.panel.contains($event.target) && close()"
                                     x-id="['dropdown-button']"
                                     class="relative"
                                >
                                    <button
                                        x-ref="button"
                                        x-on:click="toggle()"
                                        :aria-expanded="open"
                                        :aria-controls="$id('dropdown-button')"
                                        type="button"
                                        class="hover:text-primary cursor-pointer"
                                        outline="none"
                                    >
                                        <svg class="svg-hover-action w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                        </svg>
                                    </button>
                                    <div
                                        x-cloak
                                        x-ref="panel"
                                        x-show="open"
                                        x-transition.origin.top.left
                                        x-on:click.outside="close($refs.button)"
                                        :id="$id('dropdown-button')"
                                        class="absolute right-0 mt-2 w-40 rounded-md bg-white shadow-md z-50"
                                    >
                                        <a
                                            href="{{ route('division.view', [legalEntity(), $division]) }}"
                                            class="flex items-center gap-2 w-full first-of-type:rounded-t-md px-4 py-2.5 text-left text-sm text-gray-600 hover:bg-gray-50"
                                        >
                                            <svg class="w-5 h-5 text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                <path stroke="currentColor" stroke-width="2" d="M21 12c0 1.2-4.03 6-9 6s-9-4.8-9-6c0-1.2 4.03-6 9-6s9 4.8 9 6Z"/>
                                                <path stroke="currentColor" stroke-width="2" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            </svg>

                                            {{ __('forms.view') }}
                                        </a>
                                        @can('update', $division)
                                            <a
                                                href="{{ route('division.edit', [legalEntity(), $division]) }}"
                                                class="flex items-center gap-2 w-full px-4 py-2.5 text-left text-sm text-gray-600 hover:bg-gray-50"
                                            >
                                                <svg class="w-5 h-5 text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                                </svg>

                                                {{ __('forms.edit') }}
                                            </a>
                                        @endcan
                                        @can('deactivate', $division)
                                            <a
                                                x-on:click.prevent="
                                                    divisionId={{ $division->id }};
                                                    textConfirmation=@js(__('divisions.modals.deactivate.confirmation_text'));
                                                    actionType='deactivate';
                                                    actionTitle=@js(__('divisions.modals.deactivate.title'));
                                                    actionButtonText=@js(__('forms.deactivate'));
                                                    open = !open;
                                                "
                                                href="#"
                                                class="flex items-center gap-2 w-full last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"
                                            >
                                                <svg class="w-5 h-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                                </svg>

                                                {{ __('forms.deactivate') }}
                                            </a>
                                        @endcan
                                        {{-- @can('services', $division)
                                            <a
                                                href="{{ route('healthcare_service.index', [legalEntity(), $division]) }}"
                                                class="flex items-center gap-2 w-full first-of-type:rounded-t-md last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm hover:bg-gray-50 disabled:text-gray-500"
                                            >
                                                <svg class="w-5 h-5 text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13v-2a1 1 0 0 0-1-1h-.757l-.707-1.707.535-.536a1 1 0 0 0 0-1.414l-1.414-1.414a1 1 0 0 0-1.414 0l-.536.535L14 4.757V4a1 1 0 0 0-1-1h-2a1 1 0 0 0-1 1v.757l-1.707.707-.536-.535a1 1 0 0 0-1.414 0L4.929 6.343a1 1 0 0 0 0 1.414l.536.536L4.757 10H4a1 1 0 0 0-1 1v2a1 1 0 0 0 1 1h.757l.707 1.707-.535.536a1 1 0 0 0 0 1.414l1.414 1.414a1 1 0 0 0 1.414 0l.536-.535 1.707.707V20a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-.757l1.707-.708.536.536a1 1 0 0 0 1.414 0l1.414-1.414a1 1 0 0 0 0-1.414l-.535-.536.707-1.707H20a1 1 0 0 0 1-1Z"/>
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                                                </svg>
                                                {{ __('forms.services') }}
                                            </a>
                                        @endcan --}}
                                        @can('activate', $division)
                                            <a
                                                x-on:click.prevent="
                                                    divisionId={{ $division->id }};
                                                    textConfirmation=@js(__('divisions.modals.activate.confirmation_text'));
                                                    actionType='activate';
                                                    actionTitle=@js(__('divisions.modals.activate.title'));
                                                    actionButtonText=@js(__('forms.activate'));
                                                    open = !open;
                                                "
                                                href="#"
                                                class="flex items-center gap-2 w-full first-of-type:rounded-t-md last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm text-green-600 hover:bg-green-50"
                                            >
                                                <svg class="w-5 h-5 text-green-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.5 11.5 11 14l4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                </svg>

                                                {{ __('forms.activate') }}
                                            </a>
                                        @endcan
                                        @can('delete', $division)
                                            <a
                                                x-on:click.prevent="
                                                    divisionId={{ $division->id }};
                                                    textConfirmation=@js(__('divisions.modals.delete.confirmation_text'));
                                                    actionType='delete';
                                                    actionTitle=@js(__('divisions.modals.delete.title'));
                                                    actionButtonText=@js(__('forms.delete'));
                                                    open = !open;
                                                "
                                                href="#"
                                                class="flex items-center gap-2 w-full last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"
                                            >
                                                <svg class="w-5 h-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 7h14m-9 3v8m4-8v8M10 3h4a1 1 0 0 1 1 1v3H9V4a1 1 0 0 1 1-1ZM6 7h12v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7Z"/>
                                                </svg>

                                                {{ __('forms.delete') }}
                                            </a>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-black w-full p-4 border-gray-200 text-center dark:bg-gray-800 dark:border-gray-700 dark:text-white" colspan="7">
                            <p>
                                {{ __('forms.nothing_found') }}
                            </p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>

            {{--<x-pagination :pagination="$divisions" class="pagination" />--}}
            <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
                {{ $divisions->links() }}
            </div>

        </div>

    </div>



    @include('livewire.division.modal.confirmation-modal')

</div>
