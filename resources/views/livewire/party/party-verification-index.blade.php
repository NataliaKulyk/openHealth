<div>
    <x-header-navigation>
        <x-slot name="title">
            {{ __('party_verification.verification_list') }}
        </x-slot>
    </x-header-navigation>

    <x-section class="-mt-8 form shift-content">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full min-w-full text-sm text-left text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('forms.employee') }}
                    </th>

                   {{--general status --}}
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('party_verification.status') }}
                    </th>

                    {{-- DRFO --}}
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('party_verification.types.drfo') }}
                    </th>

                    {{-- DRACS (Death) --}}
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('party_verification.types.dracs_death') }}
                    </th>

                    {{-- Passport (Ministry of Internal Affairs / VHI) --}}
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('party_verification.types.mvs_passport') }} / {{ __('party_verification.types.dms_passport') }}
                    </th>

                    {{-- Change of full name --}}
                    <th scope="col" class="px-6 py-3 whitespace-nowrap">
                        {{ __('party_verification.types.dracs_name_change') }}
                    </th>

                    <th scope="col" class="px-6 py-3"></th>
                </tr>
                </thead>
                <tbody>
                @forelse($verifications as $item)
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700" wire:key="verif-{{ $item['party_id'] }}">

                        {{-- 1. Name --}}
                        <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                            {{ $item['party_name'] }}
                        </td>

                        {{-- 2. General status --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$item['verification_status'] ?? '-'" />
                        </td>

                        {{-- 3. Status of the DRFO --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$item['details']['drfo']['verification_status'] ?? '-'" />
                        </td>

                        {{-- 4. DRAC Status (Death) --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$item['details']['dracs_death']['verification_status'] ?? '-'" />
                        </td>

                        {{-- 5. Passport statuses (group) --}}
                        <td class="px-6 py-4">
                            <div class="flex flex-col space-y-2">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ __('party_verification.types.mvs_passport') }}
                                    </span>
                                    <x-verification-status-badge :status="$item['details']['mvs_passport']['verification_status'] ?? '-'" />
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                        {{ __('party_verification.types.dms_passport') }}
                                    </span>
                                    <x-verification-status-badge :status="$item['details']['dms_passport']['verification_status'] ?? '-'" />
                                </div>
                            </div>
                        </td>

                        {{-- 6. Full name change status --}}
                        <td class="px-6 py-4">
                            <x-verification-status-badge :status="$item['details']['dracs_name_change']['verification_status'] ?? '-'" />
                        </td>

                        {{-- 7. Button Details --}}
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
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
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

        {{-- PAGINATION --}}
        <div class="mt-4">
            {{ $verifications->links() }}
        </div>
    </x-section>
</div>
