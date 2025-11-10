<div>
    <x-header-navigation class="items-start" x-data="{ showFilter: false }">

        <x-slot name="title">
            {{ __('forms.details') }}
        </x-slot>

        <div class="flex flex-wrap items-end justify-between gap-4 max-w-6xl">
            <button wire:click="sync" class="button-sync flex items-center gap-2">
                @icon('refresh', 'w-4 h-4')
                {{ __('forms.synchronise_with_eHealth') }}
            </button>
        </div>
    </x-header-navigation>

    <div class="shift-content pl-3.5">

    <fieldset class="p-4 sm:p-8 sm:pb-10 mb-16 mt-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 max-w-[1280px]">
        <legend class="legend">{{ __('forms.verification_NSZU') }}</legend>
        <div class="flow-root mt-4">
            <div class="max-w-screen-xl">
                <table class="table-input w-full table-fixed min-w-[600px] text-sm">
                    <thead class="thead-input">
                    <tr>
                        <th scope="col" class="px-3 py-3 th-input w-[15%]">{{__('forms.status.label')}}</th>
                        <th scope="col" class="px-3 py-3 th-input w-[35%]">{{__('forms.reviewed_NHS')}}</th>
                        <th scope="col" class="px-3 py-3 th-input w-[50%]">{{__('forms.comment_NSZU')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td class="td-input break-words whitespace-nowrap align-top">
                            <span class="badge-red">{{__('forms.not_verified')}}</span>
                        </td>
                    </tr>
                    <tr>
                        <td class="td-input break-words whitespace-nowrap align-top">
                            <span class="badge-green">{{__('forms.status.active')}}</span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </fieldset>

        <fieldset class="p-4 sm:p-8 sm:pb-10 mb-16 mt-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 max-w-[1280px]">
            <legend class="legend">{{ __('forms.status_in_the_system') }}</legend>
            {{--@if ($status === 'active')--}}
                <div class="status-alert-green status-alert-full mb-6">
                    <span class="flex-shrink-0">
                        @icon('check-circle', 'w-5 h-5 text-green-700 mr-3')
                    </span>
                    <span class="ms-1">{{__('forms.status.active')}}</span>
                </div>
            {{--@elseif ($status === 'non_active')--}}
                <div class="status-alert-red status-alert-full mb-6">
                    <span class="flex-shrink-0">
                        @icon('alert-circle', 'w-5 h-5 text-red-500 mr-3')
                    </span>
                    <span class="ms-1">{{__('forms.status.non_active')}}</span>
                </div>
            {{--@endif--}}
        </fieldset>

        <fieldset class="p-4 sm:p-8 sm:pb-10 mb-16 mt-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 max-w-[1280px]">
            <legend class="legend">{{ __('forms.state_of_the_NMP') }}</legend>
            {{--@if ($status === 'active')--}}
            <div class="status-alert-green status-alert-full mb-6">
        <span class="flex-shrink-0">
            @icon('check-circle', 'w-5 h-5 text-green-700 mr-3')
        </span>
                <span class="ms-1">{{__('forms.status.active')}}</span>
            </div>
            {{--@elseif ($status === 'non_active')--}}
            <div class="status-alert-red status-alert-full mb-6">
        <span class="flex-shrink-0">
            @icon('alert-circle', 'w-5 h-5 text-red-500 mr-3')
        </span>
                <span class="ms-1">{{__('forms.in_state_suspension')}}</span>
            </div>
            {{--@endif--}}
            <div class="flex flex-col lg:flex-row lg:gap-x-1">
                <div class="flex-grow">
                    <div class="form-row-3">
                        <div class="form-group">
                            <input
                                id="name_legal_entity"
                                type="text"
                                placeholder=" "
                                class="peer input @error('legal-entityForm.legal-entity.name') input-error border-red-500 @enderror"
                                name="name_legal-entity"
                                wire:model.defer='legal-entityForm.legal-entity.name'
                                x-bind:disabled="isDisabled"
                            />

                            <label
                                for="name_legal_entity"
                                class="label"
                            >
                                {{ __('forms.full_name_division') }}
                            </label>

                            @error('legal-entityForm.legal-entity.name')
                            <p class="text-error">{{$message}}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <input
                                id="public_name"
                                type="text"
                                placeholder=" "
                                class="peer input @error('legal-entityForm.legal-entity.name') input-error border-red-500 @enderror"
                                name="public_name"
                                wire:model.defer='legal-entityForm.legal-entity.name'
                                x-bind:disabled="isDisabled"
                            />

                            <label
                                for="public_name"
                                class="label"
                            >
                                {{ __('forms.public_name') }}
                            </label>

                            @error('legal-entityForm.legal-entity.name')
                            <p class="text-error">{{$message}}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <input
                                id="abbreviated_name"
                                type="text"
                                placeholder=" "
                                class="peer input @error('legal-entityForm.legal-entity.name') input-error border-red-500 @enderror"
                                name="abbreviated_name"
                                wire:model.defer='legal-entityForm.legal-entity.name'
                                x-bind:disabled="isDisabled"
                            />

                            <label
                                for="abbreviated_name"
                                class="label"
                            >
                                {{ __('forms.abbreviated_name') }}
                            </label>

                            @error('legal-entityForm.legal-entity.name')
                            <p class="text-error">{{$message}}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <input
                                id="organizational_legal_form"
                                type="text"
                                placeholder=" "
                                class="peer input @error('legal-entityForm.legal-entity.name') input-error border-red-500 @enderror"
                                name="organizational_legal_form"
                                wire:model.defer='legal-entityForm.legal-entity.name'
                                x-bind:disabled="isDisabled"
                            />

                            <label
                                for="organizational_legal_form"
                                class="label"
                            >
                                {{ __('forms.organizational_legal_form') }}
                            </label>

                            @error('legal-entityForm.legal-entity.name')
                            <p class="text-error">{{$message}}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <input
                                id="address_registration_NMP"
                                type="text"
                                placeholder=" "
                                class="peer input @error('legal-entityForm.legal-entity.name') input-error border-red-500 @enderror"
                                name="address_registration_NMP"
                                wire:model.defer='legal-entityForm.legal-entity.name'
                                x-bind:disabled="isDisabled"
                            />

                            <label
                                for="address_registration_NMP"
                                class="label"
                            >
                                {{ __('forms.address_registration_NMP') }}
                            </label>

                            @error('legal-entityForm.legal-entity.name')
                            <p class="text-error">{{$message}}</p>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class=" lg:mt-0 lg:min-w-[280px] lg:-ml-6 space-y-4">
                <p class="text-base font-semibold text-gray-900 dark:text-gray-200 mb-4">{{__('Список КВЕДів:')}}</p>

                    <div class="text-sm text-gray-900 dark:text-gray-200 space-y-4">
                        <div>
                            <p class="font-semibold text-gray-600 dark:text-gray-400">Основний КВЕД:</p>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-600 dark:text-gray-400">Додаткові КВЕДи:</p>
                        </div>
                    </div>
                </div>

            </div>
        </fieldset>

    <fieldset class="p-4 sm:p-8 sm:pb-10 mb-16 mt-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 max-w-[1280px]">
        <legend class="legend">{{ __('forms.participation_reorganization') }}</legend>
        {{--@if ($status === 'active')--}}
        <div class="status-alert-green status-alert-full mb-6">
                    <span class="flex-shrink-0">
                        @icon('check-circle', 'w-5 h-5 text-green-700 mr-3')
                    </span>
            <span class="ms-1">{{__('forms.process_of_reorganization')}}</span>
        </div>
        {{--@elseif ($status === 'non_active')--}}
        <div class="status-alert-red status-alert-full mb-6">
                    <span class="flex-shrink-0">
                        @icon('alert-circle', 'w-5 h-5 text-red-500 mr-3')
                    </span>
            <span class="ms-1">{{__('forms.not_process_of_reorganization')}}</span>
        </div>
        {{--@endif--}}
        <div class=" lg:mt-0 lg:min-w-[280px] lg:-ml-1 space-y-4">
            <p class="text-base font-semibold text-gray-900 dark:text-gray-200 mb-4">{{__('Заклади, повʼязані з процесом реорганізації:')}}</p>
        </div>
        <div class="flex items-center gap-4 mt-6">
            <a href=" "
               class="cursor-pointer text-blue-600 hover:text-blue-800 flex items-center gap-1">
                @icon('download', 'w-4 h-4 text-blue-600 hover:text-blue-800')
                <span class="text-sm">{{ __('forms.download_list_employees') }}</span>
            </a>

            <a href=" "
               class="cursor-pointer text-blue-600 hover:text-blue-800 flex items-center gap-1">
                @icon('upload', 'w-4 h-4 text-blue-600 hover:text-blue-800')
                <span class="text-sm">{{ __('forms.upload_employee_list') }}</span>
            </a>
        </div>
    </fieldset>

{{--        <fieldset class="p-4 sm:p-8 sm:pb-10 mb-16 mt-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 max-w-[1280px]">--}}
{{--            <div class="p-5">--}}
{{--                @include('livewire.legal-entity.step._step_edrpou', ['isEdit' => true])--}}
{{--                @include('livewire.legal-entity.step._step_owner')--}}
{{--                @include('livewire.legal-entity.step._step_contact')--}}
{{--                @include('livewire.legal-entity.step._step_residence_address')--}}
{{--                @include('livewire.legal-entity.step._step_accreditation')--}}
{{--                @include('livewire.legal-entity.step._step_license')--}}
{{--                @include('livewire.legal-entity.step._step_additional_information')--}}
{{--                @include('livewire.legal-entity.step._step_public_offer')--}}
{{--            </div>--}}
{{--        </fieldset>--}}
    </div>
</div>

