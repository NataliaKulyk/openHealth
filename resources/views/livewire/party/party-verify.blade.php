<div x-data="{ showUpdateModal: @entangle('showUpdateModal') }"
     x-on:status-updated-close-modal.window="showUpdateModal = false">

    {{-- Breadcrumb Navigation --}}
    <x-header-navigation>
        <x-slot name="title">
            {{ __('party_verification.label') }} {{ $party->fullName ?? '' }}
        </x-slot>
    </x-header-navigation>

    {{-- Main Content Section --}}
    <x-section class="-mt-8 form shift-content">

        {{-- 1. Verification Details Table --}}
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full min-w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 w-1/5">{{ __('party_verification.label') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('party_verification.status') }}</th>
                    <th scope="col" class="px-6 py-3">{{ __('forms.reason_code') }}</th>
                    <th scope="col" class="px-6 py-3 w-2/5">{{ __('forms.ehealth_comment_recommendation') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($verificationDetails['details'] ?? [] as $key => $details)
                    @php
                        $status = data_get($details, 'verification_status');
                        $reason = data_get($details, 'verification_reason');
                        $comment = data_get($details, 'verification_comment');
                        $result = data_get($details, 'result');
                    @endphp
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200" wire:key="details-{{ $key }}">
                        {{-- Column 1: Type --}}
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white align-top whitespace-normal">
                            {{ __('party_verification.types.' . $key) }}
                        </td>

                        {{-- Column 2: Status --}}
                        <td class="px-6 py-4 text-sm align-top whitespace-normal">
                            @if($status === 'VERIFIED')
                                <span class="badge-green">{{ __('party_verification.statuses.VERIFIED') }}</span>
                            @elseif($status === 'NOT_VERIFIED')
                                <span class="badge-red">{{ __('party_verification.statuses.NOT_VERIFIED') }}</span>
                            @elseif($status === 'VERIFICATION_NEEDED')
                                <span class="badge-yellow">{{ __('party_verification.statuses.VERIFICATION_NEEDED') }}</span>
                            @elseif($status === 'VERIFICATION_NOT_NEEDED')
                                <span class="badge-gray">{{ __('party_verification.statuses.VERIFICATION_NOT_NEEDED') }}</span>
                            @elseif($status)
                                <span class="badge-red">{{ $status }}</span>
                            @else
                                <span>-</span>
                            @endif
                        </td>

                        {{-- Column 3: Reason --}}
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-top whitespace-normal">
                            <div>
                                {{ $reason ? (__('party_verification.reasons.' . $reason) ?? $reason) : '-' }}
                            </div>
                            @if($result)
                                <div class="text-xs text-gray-400">({{ __('forms.code') }}: {{ $result }})</div>
                            @endif
                        </td>

                        {{-- Column 4: Comment/Recommendation --}}
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-top whitespace-normal">
                            @if(!empty($comment))
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $comment }}</span>
                            @elseif ($status !== 'VERIFIED')
                                {{ __('party_verification.recommendations.' . $key, ['result' => $result]) }}
                            @else
                                <span>-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                            {{ __('forms.verification_details_not_loaded') }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- 2. Warning Block --}}
        @if($this->hasVerificationWarnings)
            <div class="p-4 mt-6 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                <h4 class="font-bold">{{ __('party_verification.warning.header') }}</h4>

                <ul class="mt-2 list-disc list-inside space-y-1">
                    @if($this->drfoNotVerified)
                        <li>{{ __('party_verification.warning.drfo') }}</li>
                    @endif

                    @if($this->dracsDeathNotVerified)
                        <li>{{ __('party_verification.warning.dracs_death') }}</li>
                    @endif
                </ul>

                <p class="mt-3">{{ __('party_verification.warning.footer') }}</p>
            </div>
        @endif

        {{-- 3. Action Buttons --}}
        <div class="flex items-center justify-start gap-4 mt-8">
            <a href="{{ $backUrl }}" class="button-minor">
                {{ __('forms.back') }}
            </a>

            {{-- Update Data (Modal Trigger) --}}
            <button type="button"
                    @click="showUpdateModal = true"
                    class="button-primary-outline"
            >
                {{ __('forms.update_data') }}
            </button>

            {{-- Go to Profile --}}
            <a href="{{ route('party.edit', ['legalEntity' => $legalEntity->id, 'party' => $party->id]) }}" class="button-primary">
                {{ __('forms.go_to_party_profile') }}
            </a>
        </div>
    </x-section>

    {{-- 4. Update Status Modal --}}
    <div x-show="showUpdateModal"
         class="fixed inset-0 z-50 flex items-center justify-center"
         style="display: none;"
         x-cloak>

        {{-- Backdrop --}}
        <div x-show="showUpdateModal"
             x-transition.opacity
             class="fixed inset-0 bg-black/75"
             @click="showUpdateModal = false">
        </div>

        {{-- Modal Body --}}
        <div x-show="showUpdateModal"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full max-w-2xl m-4 bg-white rounded-lg shadow dark:bg-gray-800 z-50">

            <form wire:submit.prevent="updateStatus">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between p-4 border-b border-gray-200 rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        {{ __('forms.update_verification_status_dracs') }}
                    </h3>
                    <button type="button" @click="showUpdateModal = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                {{-- Modal Content --}}
                <div class="p-6 space-y-6">

                    {{-- 1. Subject of verification --}}
                    <div class="form-group group">
                        <select wire:model.live="stream" id="stream" class="input peer px-4 py-2">
                            <option value="dracs_death">{{ __('party_verification.types.dracs_death') }}</option>
                            <option value="dracs_name_change">{{ __('party_verification.types.dracs_name_change') }}</option>
                        </select>

                        <label for="stream" class="label">{{ __('forms.subject_verification') }}</label>
                    </div>

                    {{-- 2. Status (Always VERIFIED for manual update) --}}
                    <div class="form-group group">
                        <select wire:model.defer="status" id="status" class="input peer px-4 py-2">
                            <option value="">{{ __('forms.select_statuse') }}</option>
                            <option value="VERIFIED">{{ __('party_verification.statuses.VERIFIED') }}</option>
                        </select>
                        <label for="status" class="label">{{ __('party_verification.status') }}</label>
                        @error('status') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- 3.Cause (DYNAMIC) --}}
                    <div class="form-group group" wire:key="reason-select-for-{{ $stream }}">
                        <select wire:model.defer="reason" id="reason" class="input peer px-4 py-2">
                            <option value="">{{ __('forms.choose_reason') }}</option>

                            @foreach($this->availableReasons as $reasonCode)
                                <option value="{{ $reasonCode }}">
                                    {{ __('party_verification.reasons.' . $reasonCode) }}
                                </option>
                            @endforeach

                        </select>
                        <label for="reason" class="label">{{ __('forms.reason_verification') }}</label>
                        @error('reason') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- 4. Comment --}}
                    <div class="form-group">
                        <label for="comment" class="peer appearance-none bg-white">{{ __('forms.comment') }}</label>
                        <textarea
                            id="comment"
                            wire:model.defer="comment"
                            class="textarea !text-gray-500 dark:!text-gray-400 mt-1 px-4"
                            placeholder=" ">
                        </textarea>
                        @error('comment') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="flex items-center justify-start gap-4 p-6 border-t border-gray-200 dark:border-gray-600">
                    <button type="button" @click="showUpdateModal = false" class="button-minor">
                        {{ __('forms.cancel') }}
                    </button>
                    <button type="button"
                            @click="{{ $this->canUpdateVerification ? 'showUpdateModal = true' : '' }}"
                            class="button-primary-outline {{ !$this->canUpdateVerification ? 'opacity-50 cursor-not-allowed' : '' }}"
                            :disabled="{{ !$this->canUpdateVerification ? 'true' : 'false' }}"
                            title="{{ !$this->canUpdateVerification ? __('party_verification.update_unavailable_reason') : '' }}"
                    >
                        {{ __('forms.update_data') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
