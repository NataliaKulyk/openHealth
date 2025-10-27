<div x-data="{ showUpdateModal: false }">
    {{-- Breadcrumb Navigation --}}
    <x-header-navigation>
        <x-slot name="title">
            @lang('general.verification')
        </x-slot>
    </x-header-navigation>

    {{-- Page Title --}}
    <div class="-mt-14 form shift-content">
        <p class="mt-1 text-lg text-gray-600 dark:text-gray-300">
            {{ $party->fullName }}
        </p>
    </div>

        {{-- Table Container --}}
    <x-section class="-mt-8 form shift-content">
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full min-w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3 w-1/5">@lang('general.verification')</th>
                    <th scope="col" class="px-6 py-3">@lang('forms.status.label')</th>
                    <th scope="col" class="px-6 py-3">@lang('forms.reason_code')</th>
                    <th scope="col" class="px-6 py-3 w-2/5">@lang('forms.ehealth_comment_recommendation')</th>
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
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white align-top whitespace-normal">
                            @lang('general.verification_types.' . $key)
                        </td>
                        <td class="px-6 py-4 text-sm align-top whitespace-normal">
                            @if($status === 'VERIFIED')
                                <span class="badge-green">@lang('general.verified')</span>
                            @elseif($status)
                                <span class="badge-red">@lang('general.' . strtolower($status))</span>
                            @else
                                <span>-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-top whitespace-normal">
                            <div>{{ $reason ?? '-' }}</div>
                            @if($result)
                                <div class="text-xs text-gray-400">(@lang('forms.code'): {{ $result }})</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 align-top whitespace-normal">
                            @if(!empty($comment))
                                <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $comment }}</span>
                            @elseif ($status !== 'VERIFIED')
                                @lang('general.recommendations.' . $key, ['result' => $result])
                            @else
                                <span>-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 border-gray-200">
                        <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500">
                            @lang('forms.verification_details_not_loaded')
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 mt-6 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
            @lang('general.ehealth_fitness_warning', ['status' => '"Потрібна верифікація"'])
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center justify-start gap-4 mt-8">
            <a href="{{ route('employee.index', ['legalEntity' => $legalEntity->id]) }}" class="button-minor">@lang('forms.back')</a>

            @php
                $employeeToEdit = $party->employees->first();
            @endphp

{{--  TODO redirect to party/edit from i492  --}}
            @if($employeeToEdit)
                <a href="{{ route('employee.edit', ['legalEntity' => $legalEntity->id, 'employee' => $employeeToEdit->id]) }}" class="button-primary">
                    @lang('forms.edit_personal_data')
                </a>
            @endif

            <button type="button" @click="showUpdateModal = true" class="button-primary-outline">
                @lang('forms.update_death_data')
            </button>
        </div>
    </x-section>

    {{-- Update Status Modal --}}
    <div
            x-show="showUpdateModal"
            @keydown.escape.window="showUpdateModal = false"
            {{-- Modify the listener to log --}}
            @status-updated-close-modal.window="() => { console.log('Alpine received status-updated-close-modal event!'); showUpdateModal = false; }"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="display: none;"
    >

        {{-- ... (Modal background) ... --}}
        <div x-show="showUpdateModal" x-transition.opacity class="fixed inset-0 bg-black/75"></div>

        <div
                x-show="showUpdateModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                @click.away="showUpdateModal = false"
                class="relative w-full max-w-2xl m-4 bg-white rounded-lg shadow dark:bg-gray-800"
        >
            {{-- Form and its contents remain the same --}}
            <form wire:submit.prevent="updateStatus">
                <div class="flex items-center justify-between p-4 border-b border-gray-200 rounded-t dark:border-gray-600">
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                        @lang('forms.update_verification_status_dracs')
                    </h3>

                    <button type="button" @click="showUpdateModal = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center dark:hover:bg-gray-600 dark:hover:text-white">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    <div class="form-group group">
                        <select wire:model.defer="status" id="status" class="input peer">
                            <option value="">{{ __('forms.select_statuse') }}</option>
                            <option value="VERIFIED">{{ __('forms.verified') }}</option>
                            <option value="NOT_VERIFIED">{{ __('forms.not_verified') }}</option>
                        </select>
                        <label for="status" class="label">{{ __('forms.status.label') }}</label>
                        @error('status') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group group">
                        <select wire:model.defer="reason" id="reason" class="input peer">
                            <option value="">{{ __('forms.choose_reason') }}</option>
                            <option value="MANUAL_CONFIRMED">{{ __('forms.manually_confirmed') }}</option>
                            <option value="MANUAL_NOT_CONFIRMED">{{ __('forms.no_manually_confirmed') }}</option>
                        </select>
                        <label for="reason" class="label">{{ __('forms.reason_verification') }}</label>
                        @error('reason') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label for="aboutMyself"
                               class="peer appearance-none bg-white">{{ __('forms.comment') }}</label>
                        <textarea
                                id="aboutMyself"
                                wire:model.defer="comment"
                                class="textarea !text-gray-500 dark:!text-gray-400 mt-1"
                                placeholder=" ">
                        </textarea>
                        @error('comment') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-start gap-4 p-6 border-t border-gray-200">
                    <button type="button" @click="showUpdateModal = false" class="button-minor">{{ __('forms.cancel') }}</button>
                    <button type="submit" class="button-primary">{{ __('forms.update_data') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
