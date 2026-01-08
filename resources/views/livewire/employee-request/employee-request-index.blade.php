<div>
    {{-- 1. DEFINE PERMISSIONS --}}
    @php
        $currentUser = auth()->user();

       $permissions = [
        'request_view'   => $currentUser->can('employee_request:details'),
        'request_write'  => $currentUser->can('employee_request:write'),
        'request_delete' => $currentUser->can('employee_request:write'),

        'employee_view' => false, 'employee_write' => false, 'employee_deactivate' => false
    ];
    @endphp

    <x-header-navigation class="items-start">
        <x-slot name="title">
            {{ __('forms.application_register') }}
        </x-slot>

        <div class="mt-3 ml-0 flex flex-col sm:flex-row sm:flex-wrap gap-2 self-start">
            <button
                wire:click="{{ !$this->isSync ? 'sync' : '' }}"
                wire:loading.attr="disabled"
                class="{{ $this->isSync ? 'button-sync-disabled' : 'button-sync' }} flex items-center gap-2 whitespace-nowrap"
                {{ $this->isSync ? 'disabled' : '' }}
            >
                <span wire:loading.remove wire:target="sync">@icon('refresh', 'w-4 h-4')</span>
                <span wire:loading wire:target="sync" class="animate-spin">@icon('refresh', 'w-4 h-4')</span>
                <span>{{ __('forms.sync_all') }}</span>
            </button>
        </div>

        <x-slot name="navigation">
            <div class="flex flex-col -my-4">
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
                        <label class="label">Статус</label>
                    </div>
                </div>
            </div>
        </x-slot>
    </x-header-navigation>

    <div class="shift-content-table">
        <div class="form-section-no-padding mt-6">
            <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
                <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400 table-fixed">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                    <tr>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-[25%]">{{ __('forms.full_name') }}</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-[20%]">{{ __('forms.role') }}</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-[20%]">{{ __('forms.division') }}</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap w-[15%]">{{ __('forms.created_at') }}</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap text-center w-[15%]">{{ __('forms.status.label') }}</th>
                        <th scope="col" class="px-6 py-3 whitespace-nowrap text-center w-[5%]">{{ __('forms.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($requests as $request)
                        <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">

                            {{-- Name --}}
                            <td class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white truncate">
                                @php
                                    $data = $request->revision->data ?? [];
                                    $partyData = $data['party'] ?? [];
                                    $fullName = trim(($partyData['last_name'] ?? '') . ' ' . ($partyData['first_name'] ?? '') . ' ' . ($partyData['second_name'] ?? ''));
                                @endphp
                                <span title="{{ $fullName }}">{{ $fullName ?: 'N/A' }}</span>
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap truncate">
                                @php
                                    // Based on your debug: data is inside 'employee_request_data'
                                    $posCode = $data['employee_request_data']['position'] ?? ($data['position'] ?? null);

                                    // Use dictionary with fallback to code, exactly as in EmployeeIndex
                                    $posName = $dictionaries['POSITION'][$posCode] ?? $posCode;
                                @endphp
                                <span title="{{ $posName }}">{{ $posName ?: 'N/A' }}</span>
                            </td>

                            {{-- Division --}}
                            <td class="px-6 py-4 whitespace-nowrap truncate">
                                <span title="{{ $request->division->name ?? '' }}">
                                    {{ $request->division->name ?? 'N/A' }}
                                </span>
                            </td>

                            {{-- Date --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $request->created_at ? $request->created_at->format('d.m.Y H:i') : '-' }}
                            </td>

                            {{-- Status --}}
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @if($request->status == \App\Enums\Employee\RequestStatus::NEW)
                                    <span class="badge-red">{{ $request->status->label() }}</span>
                                @elseif($request->status == \App\Enums\Employee\RequestStatus::SIGNED)
                                    <span class="badge-yellow">{{ $request->status->label() }}</span>
                                @elseif($request->status == \App\Enums\Employee\RequestStatus::APPROVED)
                                    <span class="badge-green">{{ $request->status->label() }}</span>
                                @else
                                    <span class="badge-gray">{{ $request->status->label() }}</span>
                                @endif
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-center align-top">
                                @include('livewire.employee.parts.actions-dropdown', [
                                    'position' => $request,
                                    'permissions' => $permissions
                                ])
                            </td>
                    </tr>
                @empty
                        <fieldset class="fieldset !mx-auto mt-8 shift-content">
                            <legend class="legend relative -top-5">@icon('nothing-found', 'w-28 h-28')</legend>
                            <div class="p-4 rounded-lg bg-blue-100 flex items-start mb-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-shrink-0 mt-0.5">
                                        @icon('alert-circle', 'w-5 h-5 text-blue-500 mr-3 mt-1')
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-bold text-blue-800">
                                            {{ __('forms.nothing_found') }}
                                        </p>
                                        <p class="text-sm text-blue-600">
                                            {{ __('forms.changing_search_parameters') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-8 pl-3.5 pb-8 lg:pl-8 2xl:pl-5">
            {{ $requests->links() }}
        </div>
    </div>
    <x-forms.loading wire:target="sync"/>
</div>
</div>
