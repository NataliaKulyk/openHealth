<form class="form"
      x-data="{
        licenseType: $wire.entangle('form.type'),
        licenseTypes: @js($licenseTypes),
      }"
>
    <div class="form-row-2">
        <div class="form-group">
            <input type="text"
                   name="licenseType"
                   id="licenseType"
                   class="peer input dark:text-gray-400"
                   value="{{ $form->isPrimary ? __('forms.license.is_primary') : __('forms.license.is_not_primary') }}"
                   disabled
                   placeholder=" "/>

            <label for="licenseType" class="label">{{ __('forms.license.kind') }}</label>
        </div>
        <div class="form-group">
            <input
                wire:model="form.orderNo"
                type="text"
                name="orderNumber"
                id="orderNumber"
                class="peer input"
                placeholder=" "
                required/>

            <label for="orderNumber" class="label">{{ __('forms.license.order_no') }}</label>
        </div>
    </div>
    <div class="form-row"
         x-data="{
            open: false,
            selected: licenseTypes[licenseType],
            choose(key, label) {
                this.selected = label;
                licenseType = key;
                this.open = false;
            }
        }"
    >
        <div class="relative w-full">
            <div class="input-select peer cursor-pointer whitespace-normal break-words min-h-[48px] px-3 py-2 pr-10"
                 x-on:click="open = !open"
                 :class="{ 'ring-1 ring-blue-500 border-blue-500': open }">
                <span x-text="selected || 'Оберіть тип ліцензії'"></span>
                <span
                    class="absolute right-3 top-1/2 w-2 h-2 border-r-2 border-b-2 border-gray-500 dark:border-gray-400 transform -translate-y-1/2 rotate-45 pointer-events-none"></span>
            </div>

            <ul x-show="open" x-transition x-cloak class="dropdown-panel w-full max-h-60 overflow-auto z-10">
                @foreach ($licenseTypes ?? [] as $key => $label)
                    <li>
                        <button type="button"
                                x-text="'{{ $label }}'"
                                x-on:click="choose('{{ $key }}', '{{ $label }}')"
                            @class([
                                'text-left text-sm whitespace-normal break-words px-3 py-2 w-full text-start',
                                'rounded-t-md' => $loop->first,
                                'rounded-b-md' => $loop->last,
                            ])>
                        </button>
                    </li>
                @endforeach
            </ul>
            <label class="label" for="licenseType">{{ __('forms.license.type') }}</label>
            <input type="hidden"
                   name="licenseType"
                   :value="selected"
            >
        </div>
    </div>
    <div class="form-row-2">
        <div class="form-group">
            <input wire:model="form.issuedBy"
                   type="text"
                   name="issuedBy"
                   id="issuedBy"
                   class="peer input"
                   placeholder=" "
                   required
            />
            <label for="issuedBy" class="label">{{ __('forms.license.issued_by') }}</label>
        </div>
        <div class="form-group">
            <input wire:model="form.whatLicensed"
                   type="text"
                   name="whatLicensed"
                   id="whatLicensed"
                   class="peer input"
                   placeholder=" "
                   required
            />
            <label for="whatLicensed" class="label">{{ __('forms.license.what_licensed') }}</label>
        </div>
    </div>
    <div class="form-row-2">
        <div class="form-group">
            <input wire:model="form.licenseNumber"
                   type="text"
                   name="licenseNumber"
                   id="licenseNumber"
                   class="peer input"
                   placeholder=" "
                   required
            />
            <label for="licenseNumber" class="label">{{ __('forms.license.number') }}</label>
        </div>
        <div class="form-group datepicker-wrapper relative w-full">
            <input wire:model="form.issuedDate"
                   type="text"
                   name="dateOfLicenseIssuance"
                   id="dateOfLicenseIssuance"
                   class="peer input pl-10 appearance-none datepicker-input"
                   placeholder=" "
                   required
                   datepicker-autohide
                   datepicker-format="yyyy-mm-dd"
                   datepicker-button="false"
            />
            <label for="dateOfLicenseIssuance" class="wrapped-label">{{ __('forms.license.issued_date') }}</label>
        </div>
    </div>
    <div class="form-row-2">
        <div class="form-group datepicker-wrapper relative w-full">
            <input wire:model="form.activeFromDate"
                   type="text"
                   name="activeFromDate"
                   id="activeFromDate"
                   class="peer input pl-10 appearance-none datepicker-input"
                   placeholder=" "
                   required
                   datepicker-autohide
                   datepicker-format="yyyy-mm-dd"
                   datepicker-button="false"
            />
            <label for="activeFromDate" class="wrapped-label">{{ __('forms.license.active_from_date') }}</label>
        </div>
        <div class="form-group datepicker-wrapper relative w-full">
            <input wire:model="form.expiryDate"
                   type="text"
                   name="expiryDate"
                   id="expiryDate"
                   class="peer input pl-10 appearance-none datepicker-input"
                   placeholder=" "
                   required
                   datepicker-autohide
                   datepicker-format="yyyy-mm-dd"
                   datepicker-button="false"
            />
            <label for="expiryDate" class="wrapped-label">{{ __('forms.license.expiry_date') }}</label>
        </div>
    </div>
    <div class="flex justify-start gap-4 mt-10">
        <button type="button" class="button-minor">
            Скасувати
        </button>
        <button type="submit" class="button-primary">
            Додати ліцензію
        </button>
    </div>
</form>



