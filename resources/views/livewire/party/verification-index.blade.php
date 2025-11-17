<div>
    <x-header-navigation>
        <x-slot name="title">
            Список верифікацій співробітників
        </x-slot>
    </x-header-navigation>

    <x-section class="-mt-8 form shift-content">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full min-w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">Співробітник</th>
                    <th scope="col" class="px-6 py-3">Загальний</th>
                    <th scope="col" class="px-6 py-3">ДРФО</th>
                    <th scope="col" class="px-6 py-3">ДРАЦС (Смерть)</th>
                    <th scope="col" class="px-6 py-3">Паспорт (МВС/ДМС)</th>
                    <th scope="col" class="px-6 py-3"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($parties as $party)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700"
                        wire:key="party-{{ $party->id }}">

                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                            {{ $party->fullName }}
                        </td>

                        {{-- 1. Загальний Статус --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$party->verification_status" />
                        </td>

                        {{-- 2. Статус ДРФО --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$party->drfo_status" />
                        </td>

                        {{-- 3. Статус ДРАЦС --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$party->dracs_death_status" />
                        </td>

                        {{-- 4. Статуси Паспортів --}}
                        <td class="px-6 py-4">
                            <div class="flex flex-col space-y-1">
                                <x-verification-status-badge :status="$party->mvs_passport_status" prefix="МВС:" />
                                <x-verification-status-badge :status="$party->dms_passport_status" prefix="ДМС:" />
                            </div>
                        </td>

                        {{-- 5. Посилання на деталі --}}
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('party.verification.show', ['legalEntity' => $this->legalEntity->id, 'party' => $party->id]) }}"
                               class="button-primary-outline-sm">
                                Деталі
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            Співробітники для цієї установи не знайдені.
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $parties->links() }}
        </div>
    </x-section>
</div>
