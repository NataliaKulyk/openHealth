<fieldset class="fieldset space-y-6">
    <legend class="legend">
        <h2>{{__('forms.personalData')}}</h2>
    </legend>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="space-y-6">
            {{-- Last Name --}}
            <div class="form-group">
                <label for="lastName" class="label-main">{{__('forms.last_name')}} *</label>
                <input wire:model="form.party.lastName" type="text" id="lastName" class="input @error('form.party.lastName') input-error @enderror" required />
                @error('form.party.lastName') <p class="text-error">{{$message}}</p> @enderror
            </div>

            {{-- First Name --}}
            <div class="form-group">
                <label for="firstName" class="label-main">{{__('forms.first_name')}} *</label>
                <input wire:model="form.party.firstName" type="text" id="firstName" class="input @error('form.party.firstName') input-error @enderror" required />
                @error('form.party.firstName') <p class="text-error">{{$message}}</p> @enderror
            </div>

            {{-- Second Name --}}
            <div class="form-group">
                <label for="secondName" class="label-main">{{__('forms.second_name')}}</label>
                <input wire:model="form.party.secondName" type="text" id="secondName" class="input @error('form.party.secondName') input-error @enderror"/>
                @error('form.party.secondName') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>

        <div class="space-y-6">
            {{-- Gender --}}
            <div class="form-group">
                <label for="employeeGender" class="label-main">{{__('forms.gender')}} *</label>
                <select wire:model="form.party.gender" id="employeeGender" class="input-select @error('form.party.gender') input-error @enderror" required>
                    <option value="" disabled selected>{{__('forms.select')}}</option>
                    @foreach($this->dictionaries['GENDER'] as $k=>$gender)
                        <option value="{{$k}}">{{$gender}}</option>
                    @endforeach
                </select>
                @error('form.party.gender') <p class="text-error">{{$message}}</p> @enderror
            </div>

            {{-- Birth Date --}}
            <div class="form-group">
                <label for="birthDate" class="label-main">{{__('forms.birth_date')}} *</label>
                <div class="relative">
                    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                    </div>
                    <input wire:model="form.party.birthDate"
                           datepicker
                           datepicker-format="yyyy-mm-dd"
                           datepicker-max-date="{{ now()->format('Y-m-d') }}"
                           type="text"
                           id="birthDate"
                           class="input datepicker-input ps-10 @error('form.party.birthDate') input-error @enderror"
                           placeholder="{{__('forms.select')}}"
                           required />
                </div>
                @error('form.party.birthDate') <p class="text-error">{{$message}}</p> @enderror
            </div>

            {{-- Working Experience --}}
            <div class="form-group">
                <label for="workingExperience" class="label-main">{{__('forms.workingExperience')}} *</label>
                <input wire:model="form.party.workingExperience" type="number" id="workingExperience" class="input @error('form.party.workingExperience') input-error @enderror" min="0" required />
                @error('form.party.workingExperience') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
    </div>

    <div class="mt-8"
         x-data="{
         noTaxId: $wire.entangle('form.party.noTaxId').live,
         isLocked: @js($this->lockPartyFields || !empty($this->employeeId))
     }"
    >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 items-start">

            <div class="form-group">
                <label
                    for="taxId"
                    class="label-main"
                    x-text="noTaxId ? '{{ __('forms.document_no_tax_id') }} *' : '{{ __('forms.tax_id') }} *'"
                ></label>

                <input
                    wire:model="form.party.taxId"
                    type="text"
                    id="taxId"
                    class="input peer disabled:bg-gray-200 disabled:cursor-not-allowed @error('form.party.taxId') input-error @enderror"
                    :disabled="isLocked"
                    required
                />
                @error('form.party.taxId')
                <p class="text-error">{{$message}}</p>
                @enderror
            </div>

            <div class="pt-8">
                <div class="flex items-center">
                    <input
                        x-model="noTaxId"
                        id="no-tax-id-checkbox"
                        type="checkbox"
                        class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500 disabled:bg-gray-200"
                        :disabled="isLocked"
                    >
                    <label for="no-tax-id-checkbox" class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">
                        {{ __('forms.no_tax_id') }}
                    </label>
                </div>
            </div>

        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="form-group">
            <label for="email" class="label-main">{{__('forms.email')}} *</label>
            <input wire:model="form.party.email" type="text" id="email" class="input peer disabled:bg-gray-200 disabled:cursor-not-allowed @error('form.party.email') input-error @enderror" required @if(isset($this->employeeId) || $this->lockPartyFields) disabled @endif />
            @error('form.party.email') <p class="text-error">{{$message}}</p> @enderror
        </div>
    </div>

    <div>
        <label for="aboutMyself" class="label-main">{{__('forms.aboutMyself')}}</label>
        <textarea wire:model="form.party.aboutMyself" id="aboutMyself" class="textarea" rows="4"></textarea>
    </div>
    {{-- Using Alpine to dynamically add and remove phone input fields --}}
    <div class="mb-4" x-data="{ phones: $wire.entangle('form.party.phones') }">

        <template x-for="(phone, index) in phones" :key="index">
            <div class="form-row-3 md:mb-0">

                <div class="form-group group">
                    <label for="phoneType-@{{ index }}" class="sr-only">{{__('forms.type_mobile')}}</label>
                    <select x-model = "phone.type" id="phoneType-@{{ index }}" class="input-select peer"
                            :class="{ 'input-error': $wire.errors.has('form.party.phones.' + index + '.type') }"
                            required>
                        <option selected>{{__('forms.type_mobile')}} *</option>
                        @foreach($this->dictionaries['PHONE_TYPE'] as $k => $phoneType )
                            <option value="{{$k}}">{{$phoneType}}</option>
                        @endforeach
                    </select>
                    <p class="text-error" x-text="$wire.errors.get('form.party.phones.' + index + '.type')" x-show="$wire.errors.has('form.party.phones.' + index + '.type')"></p>
                </div>

                <div class="form-group group"
                     x-data
                     x-init=
                         "
                        const inputElement = $el.querySelector('input[type=\'tel\']');
                        const maskOptions = {
                            mask: '+{380} (00) 000-00-00',
                            lazy: false,
                            placeholderChar: '_'
                        };

                        const mask = IMask(inputElement, maskOptions);
                        mask.value = phone.number || '';
                        mask.on('accept', () => {
                            const rawValue = mask.value;
                            const digits = rawValue.replace(/[^0-9]/g, '');
                            const cleanValue = `+${digits}`;

                            if (phone.number !== cleanValue) {
                                phone.number = cleanValue;
                            }
                        });
                        "
                >
                    <input
                        type="tel"
                        name="phone-@{{ index }}"
                        id="phoneNumber-@{{ index }}"
                        class="input peer"
                        :class="{ 'input-error': $wire.errors.has('form.party.phones.' + index + '.number') }"
                        placeholder=" "
                        required
                    />
                    <label for="phoneNumber-@{{ index }}" class="label">
                        {{__('forms.phone')}}
                    </label>
                    <p class="text-error text-xs" x-show="$wire.errors.has('form.party.phones.' + index + '.number')">
                        <span x-text="$wire.errors.get('form.party.phones.' + index + '.number')"></span>
                    </p>
                </div>
                <template x-if="index == phones.length - 1 & index != 0">
                    <button x-on:click="phones.pop(), index--"
                            class="item-remove"
                    >
                        {{__('forms.remove_phone')}}
                    </button>
                </template>
                <template x-if="index == phones.length - 1">
                    <button x-on:click="phones.push({ type: '', number: '' })"
                            class="item-add lg:justify-self-start"
                            :class="{ 'lg:justify-self-start': index > 0 }"
                    >
                        {{__('forms.add_phone')}}
                    </button>
                </template>
            </div>
        </template>
    </div>
</fieldset>



