<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('Деталі ліцензії') }}</x-slot>
    </x-section-navigation>
    <form class="form">
        <div class="form-row-2">
            <div class="form-group">
                <input {{--wire:model="form.party.kind"--}} type="text" name="kind" id="kind" class="peer input dark:text-gray-400" value="Основна" placeholder=" " required disabled />
                <label for="kind" class="label">{{__('forms.license.kind')}}</label>
                @error('form.party.kind') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input {{--wire:model="form.party.order_no"--}} type="text" name="order_no" id="order_no" class="peer input dark:text-gray-400" value="123123" placeholder=" " required disabled />
                <label for="order_no" class="label">{{__('forms.license.order_no')}}</label>
                @error('form.party.order_no') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <input {{--wire:model="form.party.type"--}} type="text" name="type" id="type" class="peer input dark:text-gray-400" value="Провадження господарської діяльності з медичної практики" placeholder=" " required disabled />
                <label for="type" class="label">{{__('forms.license.type')}}</label>
                @error('form.party.type') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input {{--wire:model="form.party.issued_by"--}} type="text" name="issued_by" id="issued_by" class="peer input dark:text-gray-400" value="МОЗ" placeholder=" " required disabled />
                <label for="issued_by" class="label">{{__('forms.license.issued_by')}}</label>
                @error('form.party.issued_by') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input {{--wire:model="form.party.what_licensed"--}} type="text" name="what_licensed" id="what_licensed" class="peer input dark:text-gray-400" value="Лікарська діяльність" placeholder=" " required disabled />
                <label for="what_licensed" class="label">{{__('forms.license.what_licensed')}}</label>
                @error('form.party.what_licensed') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input {{--wire:model="form.party.number"--}} type="text" name="number" id="number" class="peer input dark:text-gray-400" value="1231" placeholder=" " required disabled />
                <label for="number" class="label">{{__('forms.license.number')}}</label>
                @error('form.party.number') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input {{--wire:model="form.party.issued_date"--}} type="text" name="issued_date" id="issued_date" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" value="2025-02-02" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false" disabled />
                <label for="issued_date" class="wrapped-label">{{__('forms.license.issued_date')}}</label>
                @error('form.party.issued_date') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group datepicker-wrapper relative w-full">
                <input {{--wire:model="form.party.active_from_date"--}} type="text" name="active_from_date" id="active_from_date" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" value="2025-02-02" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false" disabled />
                <label for="active_from_date" class="wrapped-label">{{__('forms.license.active_from_date')}}</label>
                @error('form.party.active_from_date') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input {{--wire:model="form.party.expiry_date"--}} type="text" name="expiry_date" id="expiry_date" class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" value="2026-02-02" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false" disabled/>
                <label for="expiry_date" class="wrapped-label">{{__('forms.license.expiry_date')}}</label>
                @error('form.party.expiry_date') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="flex justify-start gap-4 mt-10">
            <button type="button" class="button-minor">
                Скасувати
            </button>
        </div>
    </form>
</div>
