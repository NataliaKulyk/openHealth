<div>
    <x-header-navigation>
        <x-slot name="title">
            {{ __('party_verification.verification_list') }}
        </x-slot>
    </x-header-navigation>

    <x-section class="-mt-8 form shift-content">

        {{-- 7.5.1: Death Status Filter (DRACS Death) --}}
        <div class="mb-4 flex justify-end">
            <div class="w-64">
                <label for="dracs_filter" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">
                    {{ __('party_verification.types.dracs_death') }}
                </label>
                <select wire:model.live="dracsDeathStatus" id="dracs_filter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
                    <option value="">{{ __('forms.all') }}</option>
                    <option value="NOT_VERIFIED">{{ __('party_verification.statuses.NOT_VERIFIED') }}</option>
                    <option value="VERIFIED">{{ __('party_verification.statuses.VERIFIED') }}</option>
                </select>
            </div>
        </div>

        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full min-w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('forms.employee') }}
                    </th>

                    {{-- 7.4.2: We leave only DRFO and DRACS Death --}}

                    {{-- DRFO --}}
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('party_verification.types.drfo') }}
                    </th>

                    {{-- DRACS (Death) --}}
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('party_verification.types.dracs_death') }}
                    </th>

                    <th scope="col" class="px-6 py-3">
                        {{-- Actions column --}}
                    </th>
                </tr>
                </thead>
                <tbody>
                @forelse($verifications as $item)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200"
                        wire:key="verif-{{ $item['party_id'] }}">

                        {{-- Employee Name --}}
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                            {{ $item['party_name'] ?? 'Unknown' }}
                        </td>

                        {{-- DRFO Status --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$item['details']['drfo']['verification_status'] ?? '-'" />
                        </td>

                        {{-- DRACS Death Status --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$item['details']['dracs_death']['verification_status'] ?? '-'" />
                        </td>

                        {{-- Details Button --}}
                        <td class="px-6 py-4 text-right">
                            @if($item['local_id'])
                                <a href="{{ route('party.verification.show', ['legalEntity' => $legalEntity->id, 'party' => $item['local_id']]) }}"
                                   class="button-primary-outline-sm whitespace-nowrap">
                                    {{ __('forms.details') }}
                                </a>
                            @else
                                <span class="text-xs text-gray-400 italic" title="{{ __('forms.party_not_found_locally') }}">
                                    N/A
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center justify-center gap-2">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <span>{{ __('forms.nothing_found') }}</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $verifications->links() }}
        </div>
    </x-section>
</div>
