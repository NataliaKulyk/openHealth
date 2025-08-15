<div>
    <x-section-navigation x-data="{ showFilter: false }" class="">
        <x-slot name="title">
            {{ __('Місця надання послуг') }}
        </x-slot>
    </x-section-navigation>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="w-96">
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
                    <div class="form-group group w-full relative">
                        <input type="text"
                               id="division_search"
                               placeholder=" "
                               class="input peer"
                               {{--wire:model.live.debounce.300ms="search"--}}
                               autocomplete="off" />
                        <label for="division_search" class="label">Назва</label>
                    </div>
                </x-slot>
            </x-forms.form-group>
        </div>
        <div class="ml-auto flex items-center gap-2 self-start -mt-17 translate-x-2">
            <a href="{{ route('division.create', [legalEntity()]) }}"
               type="button"
               class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800">
                {{ __('Додати місце надання послуг') }}
            </a>
            <button wire:click="syncDivisions" class="button-sync">
                {{ __('forms.synchronise_with_eHealth') }}
            </button>
        </div>
    </div>

    <div class="inline-block min-w-full align-middle mt-4">
        <div class="shadow overflow-hidden sm:rounded-lg">
            <table class="table-input w-full table-fixed">
                <thead class="thead-input">
                <tr>
                    <th scope="col" class="th-input">ID E-health</th>
                    <th scope="col" class="th-input">Назва</th>
                    <th scope="col" class="th-input">Тип</th>
                    <th scope="col" class="th-input">Телефон</th>
                    <th scope="col" class="th-input">Email</th>
                    <th scope="col" class="th-input">Статус</th>
                    <th scope="col" class="th-input text-center">Дія</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($divisions as $division)
                    <tr x-data="{ divisionTypes: @entangle('dictionaries.DIVISION_TYPE') }">
                        <td class="td-input break-words whitespace-normal align-top">
                            <p>{{ $division->uuid ?? '' }}</p>
                        </td>
                        <td class="td-input break-words whitespace-normal align-top">
                            <p>{{ $division->name ?? '' }}</p>
                        </td>
                        <td x-text="divisionTypes['{{ $division->type }}']" class="td-input break-words whitespace-normal align-top"></td>
                        <td class="td-input break-words whitespace-normal align-top">
                            <p>{{ $division->phones['number'] ?? '' }}</p>
                        </td>
                        <td class="td-input break-words whitespace-normal align-top">
                            <p>{{ $division->email ?? '' }}</p>
                        </td>
                        <td class="td-input break-words whitespace-normal align-top">
                            @if ($division->status == 'INACTIVE')
                                <span class="badge-red">{{ __('Не активний') }}</span>
                            @else
                                <span class="badge-green">{{ __('Активний') }}</span>
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
                                        class="hover:text-primary"
                                    >
                                        <svg class="svg-hover-action w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                        </svg>
                                    </button>
                                    <div
                                        x-ref="panel"
                                        x-show="open"
                                        x-transition.origin.top.left
                                        x-on:click.outside="close($refs.button)"
                                        :id="$id('dropdown-button')"
                                        class="absolute right-0 mt-2 w-40 rounded-md bg-white shadow-md z-50"
                                    >
                                        @if ($division->status == 'ACTIVE')
                                            <a
                                                href="{{ route('division.edit', [legalEntity(), $division]) }}"
                                                class="flex items-center gap-2 w-full first-of-type:rounded-t-md px-4 py-2.5 text-left text-sm text-gray-600 hover:bg-gray-50"
                                            >
                                                <svg class="w-5 h-5 text-gray-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="m14.304 4.844 2.852 2.852M7 7H4a1 1 0 0 0-1 1v10a1 1 0 0 0 1 1h11a1 1 0 0 0 1-1v-4.5m2.409-9.91a2.017 2.017 0 0 1 0 2.853l-6.844 6.844L8 14l.713-3.565 6.844-6.844a2.015 2.015 0 0 1 2.852 0Z"/>
                                                </svg>
                                                {{ __('forms.edit') }}
                                            </a>
                                            <a
                                                wire:click="deactivate({{ $division }}); open = !open"
                                                href="#"
                                                class="flex items-center gap-2 w-full last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm text-red-600 hover:bg-red-50"
                                            >
                                                <svg class="w-5 h-5 text-red-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="m15 9-6 6m0-6 6 6m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                                                </svg>
                                                {{ __('forms.deactivate') }}
                                            </a>
                                            <a
                                                href="{{ route('healthcare_service.index', [legalEntity(), $division]) }}"
                                                class="flex items-center gap-2 w-full first-of-type:rounded-t-md last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm hover:bg-gray-50 disabled:text-gray-500"
                                            >
                                                {{ __('forms.services') }}
                                            </a>
                                        @else
                                            <a
                                                wire:click="activate({{ $division }}); open = !open"
                                                href="#"
                                                class="flex items-center gap-2 w-full first-of-type:rounded-t-md last-of-type:rounded-b-md px-4 py-2.5 text-left text-sm hover:bg-gray-50 disabled:text-gray-500"
                                            >
                                                {{ __('forms.activate') }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="text-black w-full p-4 border-gray-200 text-center dark:bg-gray-800 dark:border-gray-700 dark:text-white" colspan="7">
                            <p>
                                {{ __('Нічого не знайдено') }}
                            </p>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            <x-pagination :pagination="$divisions" class="pagination" style="margin-block-start: 20px;"/>
        </div>
    </div>

{{--@include('livewire.division._parts._division_form')--}}
