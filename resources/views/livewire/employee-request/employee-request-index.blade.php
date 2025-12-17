<div>
    <x-header-navigation class="items-start">
        <x-slot name="title">
            {{ __('forms.application_register') }}
        </x-slot>

        <div class="mt-3 ml-0 flex flex-col sm:flex-row sm:flex-wrap gap-2 self-start">
            <button wire:click="sync" wire:loading.attr="disabled" class="button-sync flex items-center gap-2">
                <span wire:loading.remove wire:target="syncAll">@icon('refresh', 'w-4 h-4')</span>
                <span wire:loading wire:target="syncAll" class="animate-spin">@icon('refresh', 'w-4 h-4')</span>
                <span>{{ __('forms.sync_all') }}</span>
            </button>
        </div>

        <x-slot name="navigation">
            <div class="flex flex-col -my-4">
                {{-- Filters --}}
                <div class="form-row-4">
                    <div class="form-group group">
                        <input type="text"
                               wire:model.live.debounce.500ms="search"
                               class="input peer"
                               placeholder=" " />
                        <label class="label">{{ __('forms.search_name') }}</label>
                    </div>

                    <div class="form-group group">
                        <select wire:model.live="status" class="input peer">
                            <option value="">Всі статуси</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                        <label class="label">{{ __('forms.status.label') }}</label>
                    </div>
                </div>
            </div>

        </x-slot>
    </x-header-navigation>

    <div class="flow-root mt-8 shift-content pl-3.5">
        <div class="max-w-screen-xl">
            <div class="relative shadow-md sm:rounded-lg">
                <table
                    class="w-full min-w-[1100px] table-fixed text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 w-[15%]">{{ __('forms.created_at') }}</th>
                        <th class="px-6 py-3 w-[35%]">{{ __('forms.full_name') }}</th>
                        <th class="px-6 py-3 w-[25%]">{{ __('forms.position') }}</th>
                        <th class="px-6 py-3 w-[15%]">{{ __('forms.status.label') }}</th>
                        <th class="px-6 py-3 w-[10%] whitespace-nowrap text-center">{{ __('forms.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $request)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">

                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                {{ $request->created_at->format('d.m.Y H:i') }}
                            </td>

                            <th scope="row"
                                class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white break-words whitespace-normal align-top"
                            >
                                <p>{{ $request->party->fullName ?? 'Невідома особа' }}</p>
                            </th>

                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                <p>{{ $dictionaries['POSITION'][$request->position] ?? $request->position }}</p>
                                <p class="text-xs text-gray-500">{{ $request->division->name ?? '' }}</p>
                            </td>

                            <td class="px-6 py-4 break-words whitespace-normal align-top">
                                @if($request->status == \App\Enums\Employee\RequestStatus::APPROVED)
                                    <span class="badge-green">{{ $request->status->label() }}</span>
                                @elseif($request->status == \App\Enums\Employee\RequestStatus::REJECTED)
                                    <span class="badge-red">{{ $request->status->label() }}</span>
                                @elseif($request->status == \App\Enums\Employee\RequestStatus::SIGNED)
                                    <span class="badge-yellow">{{ $request->status->label() }}</span>
                                @else
                                    <span class="badge-gray">{{ $request->status->label() }}</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center align-top">
                                @include('livewire.employee.parts.actions-dropdown', [
                                    'position' => $request
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-black w-full p-4 border-gray-200 text-center dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                                colspan="5"
                            >
                                <p>{{ __('forms.nothing_found') }}</p>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
                {{ $requests->links() }}
            </div>
        </div>
        <x-forms.loading/>
    </div>
</div>
