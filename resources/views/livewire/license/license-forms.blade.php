<body class="bg-white dark:bg-gray-800 min-h-screen text-gray-900 dark:text-white">
<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('Нова додаткова ліцензія') }}</x-slot>
    </x-section-navigation>
    <form class="form">
        <div class="form-row-2">
            <div class="form-group">
                <input wire:model="form.party.kind" type="text" name="kind" id="kind" class="peer input dark:text-gray-400" value="Додаткова" placeholder=" " required />
                <label for="kind" class="label">{{__('forms.license.kind')}}</label>
                @error('form.party.kind') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input wire:model="form.party.order_no" type="text" name="order_no" id="order_no" class="peer input" placeholder=" " required />
                <label for="order_no" class="label">{{__('forms.license.order_no')}}</label>
                @error('form.party.order_no') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row" x-data="{
            open: false,
            selected: '',
            choose(option) {
                this.selected = option;
                this.open = false;
            }
        }">
            <div class="relative w-full">
                <div class="input-select peer cursor-pointer whitespace-normal break-words min-h-[48px] px-3 py-2 pr-10"
                     x-on:click="open = !open"
                     :class="{ 'ring-1 ring-blue-500 border-blue-500': open }">
                    <span x-text="selected || 'Оберіть тип ліцензії'"></span>
                    <span class="absolute right-3 top-1/2 w-2 h-2 border-r-2 border-b-2 border-gray-500 dark:border-gray-400 transform -translate-y-1/2 rotate-45 pointer-events-none"></span>
                </div>

                <ul x-show="open" x-transition x-cloak class="dropdown-panel w-full max-h-60 overflow-auto z-10">
                    @foreach ($dictionaries['LICENSE_TYPE'] ?? [] as $key => $label)
                        <li>
                            <button type="button"
                                    x-text="'{{ $label }}'"
                                    x-on:click="choose('{{ $label }}')"
                                @class([
                                    'text-left text-sm whitespace-normal break-words px-3 py-2 w-full text-start',
                                    'rounded-t-md' => $loop->first,
                                    'rounded-b-md' => $loop->last,
                                ])>
                            </button>
                        </li>
                    @endforeach
                </ul>
                <label class="label">{{__('forms.license.type')}}</label>
                <input wire:model="form.party.type" type="hidden" name="type" :value="selected">
                @error('form.party.type') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input wire:model="form.party.issued_by" type="text" name="issued_by" id="issued_by" class="peer input" placeholder=" " required />
                <label for="issued_by" class="label">{{__('forms.license.issued_by')}}</label>
                @error('form.party.issued_by') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input wire:model="form.party.what_licensed" type="text" name="what_licensed" id="what_licensed" class="peer input" placeholder=" " required />
                <label for="what_licensed" class="label">{{__('forms.license.what_licensed')}}</label>
                @error('form.party.what_licensed') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <input wire:model="form.party.number" type="text" name="number" id="number" class="peer input" placeholder=" " required />
                <label for="number" class="label">{{__('forms.license.number')}}</label>
                @error('form.party.number') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input wire:model="form.party.issued_date" type="text" name="issued_date" id="issued_date" class="peer input pl-10 appearance-none datepicker-input" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="issued_date" class="wrapped-label">{{__('forms.license.issued_date')}}</label>
                @error('form.party.issued_date') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group datepicker-wrapper relative w-full">
                <input wire:model="form.party.active_from_date" type="text" name="active_from_date" id="active_from_date" class="peer input pl-10 appearance-none datepicker-input" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="active_from_date" class="wrapped-label">{{__('forms.license.active_from_date')}}</label>
                @error('form.party.active_from_date') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group datepicker-wrapper relative w-full">
                <input wire:model="form.party.expiry_date" type="text" name="expiry_date" id="expiry_date" class="peer input pl-10 appearance-none datepicker-input" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="expiry_date" class="wrapped-label">{{__('forms.license.expiry_date')}}</label>
                @error('form.party.expiry_date') <p class="text-error">{{$message}}</p> @enderror
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
</div>
</body>
