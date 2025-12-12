<div>
    <x-header-navigation class="items-start">
        <x-slot name="title">
            Реєстр заявок співробітників
        </x-slot>

        <x-slot name="navigation">
            <div class="flex flex-col sm:flex-row gap-4 w-full justify-between items-end">
                {{-- Filters --}}
                <div class="flex gap-4 w-full sm:w-auto">
                    <div class="form-group w-64">
                        <input type="text"
                               wire:model.live.debounce.500ms="search"
                               class="input peer"
                               placeholder=" " />
                        <label class="label">Пошук за ПІБ</label>
                    </div>

                    <div class="form-group w-48">
                        <select wire:model.live="status" class="input peer">
                            <option value="">Всі статуси</option>
                            @foreach($statuses as $st)
                                <option value="{{ $st->value }}">{{ $st->label() }}</option>
                            @endforeach
                        </select>
                        <label class="label">Статус</label>
                    </div>
                </div>

                {{-- Mass Sync Button --}}
                <div>
                    <button wire:click="sync" wire:loading.attr="disabled" class="button-sync flex items-center gap-2">
                        <span wire:loading.remove wire:target="syncAll">@icon('refresh', 'w-4 h-4')</span>
                        <span wire:loading wire:target="syncAll" class="animate-spin">@icon('refresh', 'w-4 h-4')</span>
                        <span>Синхронізувати всі</span>
                    </button>
                </div>
            </div>
        </x-slot>
    </x-header-navigation>

    <x-section class="mt-6">
        <div class="table-container-responsive">
            <table class="table w-full text-sm text-left">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th class="px-4 py-3">Дата створення</th>
                    <th class="px-4 py-3">ПІБ</th>
                    <th class="px-4 py-3">Посада</th>
                    <th class="px-4 py-3">Статус</th>
                    <th class="px-4 py-3 text-center">Дії</th>
                </tr>
                </thead>
                <tbody>
                @forelse($requests as $request)
                    <tr class="border-b dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                        <td class="px-4 py-3">
                            {{ $request->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                            {{ $request->party->fullName ?? 'Невідома особа' }}
                        </td>
                        <td class="px-4 py-3">
                            {{ $dictionaries['POSITION'][$request->position] ?? $request->position }}
                            <div class="text-xs text-gray-500">{{ $request->division->name ?? '' }}</div>
                        </td>

                        <td class="px-4 py-3">
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

                        {{-- ACTION COLUMN --}}
                        <td class="px-4 py-3 text-center">
                            @include('livewire.employee.parts.actions-dropdown', [
                                'position' => $request
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            Заявок не знайдено
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $requests->links() }}
        </div>
    </x-section>
</div>
