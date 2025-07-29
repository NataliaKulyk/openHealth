<div>
    <x-section-navigation x-data="{ showFilter: false }">

        <x-slot name="title">
            {{ __('forms.contract.contracts') }}
        </x-slot>
    </x-section-navigation>
    <div class="flex flex-wrap items-end justify-between gap-4">
    <div class="w-96">
        <x-forms.form-group>
            <x-slot name="label">
                <label for="employee_search" class="text-sm font-medium text-gray-900 dark:text-white block mb-2 flex items-center gap-1">
                    <span>{{ __('forms.contract.show') }}</span>
                </label>
            </x-slot>
            <x-slot name="input">
                <div class="form-group group w-full relative top-[12px]">
                    <input type="text" id="employee_search" placeholder=" " class="input peer" {{--wire:model.live.debounce.300ms="search"--}} autocomplete="off" />
                    <label for="employee_search" class="label">{{ __('forms.contract.showContract') }}</label>
                </div>
            </x-slot>
        </x-forms.form-group>
    </div>
    <div class="flex items-center space-x-2 pt-5">
        <a {{--href="{{ route('employee-request.create', ['legalEntity' => legalEntity()->id]) }}"--}}
           class="button-primary">{{ __('forms.contract.new_contract') }}</a>
        <button {{--wire:click="sync"--}} type="button" class="button-sync">
            {{ __('forms.synchronise_with_eHealth') }}
        </button>
    </div>
    </div>
</div>
