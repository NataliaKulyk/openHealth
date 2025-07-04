<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{__('forms.personalData')}}</h2>
    </legend>
    <div class="form">
        <div class="form-row-3">
            <div class="form-group">
                <input wire:model="form.party.lastName" type="text" name="lastName" id="lastName" class="peer input text-gray-500" placeholder=" " required />
                <label for="lastName" class="label">{{__('forms.last_name')}}</label>
                @error('form.party.lastName') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input wire:model="form.party.firstName"  type="text" name="firstName" id="firstName" class="peer input text-gray-500" placeholder=" " required />
                <label for="firstName" class="label">{{__('forms.first_name')}}</label>
                @error('form.party.firstName') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <input wire:model="form.party.secondName" type="text" name="secondName" id="secondName" class="peer input text-gray-500" placeholder=" " />
                <label for="secondName" class="label">{{ __('forms.second_name') }}</label>
                @error('form.party.secondName') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <select wire:model="form.party.gender" name="employeeGender" id="employeeGender" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" required>
                    <option value="" disabled selected hidden>{{ __('forms.select') }} {{ __('forms.gender') }}</option>
                    @foreach($this->dictionaries['GENDER'] as $k => $gender)
                        <option value="{{ $k }}">{{ $gender }}</option>
                    @endforeach
                </select>
                <label for="employeeGender" class="label">{{ __('forms.gender') }}</label>
                @error('form.party.gender')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group datepicker-wrapper relative w-full">
                <input wire:model="form.party.birthDate" type="text" name="birthDate" id="birthDate" class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="birthDate" class="wrapped-label">{{__('forms.birth_date')}}</label>
                @error('form.party.birthDate') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input wire:model="form.party.workingExperience" type="number" name="workingExperience" id="workingExperience" class="peer input text-gray-500" placeholder=" " required />
                <label for="workingExperience" class="label">{{__('forms.workingExperience')}}</label>
                @error('form.party.workingExperience') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>

        <div class="form-row-3" x-data="{ noTaxId: $wire.entangle('form.party.noTaxId'), isLocked: @js($this->lockPartyFields || !empty($this->employeeId)) }">
            <div class="form-group group relative z-0">
                <input wire:model="form.party.taxId" required id="taxId" type="text" maxlength="10" placeholder=" " class="input peer text-gray-500 @error('form.party.taxId') input-error @enderror" :disabled="noTaxId || isLocked" />
                @error('form.party.taxId') <p id="partyTaxIdErrorHelp" class="text-error">{{ $message }}</p> @enderror
                <label for="taxId" class="label z-10" x-text="noTaxId ? '{{ __('forms.document_no_tax_id') }}' : '{{ __('forms.tax_id') }}'"></label>
            </div>
            <div class="form-group group">
                <div class="mt-3">
                    <input x-model="noTaxId" type="checkbox" id="noTaxId" class="default-checkbox text-blue-500 focus:ring-blue-300" :disabled="isLocked">
                    <label for="noTaxId" class="ms-2 text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('forms.no_tax_id') }}</label>
                </div>
            </div>
        </div>

        {{-- Phones Section --}}
        <div class="space-y-2">
            <div class="space-y-4">
                @foreach($form->party['phones'] as $index => $phone)
                    <div wire:key="phone-{{ $index }}" class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">

                        {{-- Phone Type Select --}}
                        <div class="form-group">
                            <select wire:model.defer="form.party.phones.{{$index}}.type" class="input-select @error('form.party.phones.'.$index.'.type') input-error @enderror" required >
                                <option value="">{{__('forms.type_mobile')}} *</option>
                                @foreach($this->dictionaries['PHONE_TYPE'] as $key => $phoneType)
                                    <option value="{{$key}}">{{$phoneType}}</option>
                                @endforeach
                            </select>
                            <label class="label">{{ __('forms.phone_type') }}</label>
                            @error('form.party.phones.'.$index.'.type') <p class="text-error">{{ $message }}</p> @enderror
                        </div>

                        {{-- Phone Number Input with Alpine.js Mask Plugin --}}
                        <div class="form-group phone-wrapper">
                            <input wire:model.defer="form.party.phones.{{$index}}.number"  required type="tel" placeholder=" " class="peer input pl-10 with-leading-icon text-gray-500" x-model="phones[index].number" x-mask="+380999999999" :id="$id('phone', '_number' + index)" :class="{ 'input-error border-red-500': errors[legalEntityForm.phones.${index}.number] }" />
                            <label for="phoneNumber" class="wrapped-label">{{ __('forms.phone') }}</label>
                            @error('form.party.phoneNumber') <p class="text-error">{{ $message }}</p> @enderror
                        </div>

                        <button type="button" wire:click="addPhone" class="item-add">
                            <span>{{__('forms.add_phone')}}</span>
                        </button>

                        {{-- Remove Button --}}
                        @if(count($form->party['phones']) > 1 && !$this->lockPartyFields)
                            <button type="button" wire:click="removePhone({{ $index }})" class="item-remove text-red-600 hover:text-red-800 justify-self-start">
                                <span>{{__('forms.remove_phone')}}</span>
                            </button>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-row-3">
            <div class="form-group">
                <input wire:model="form.party.email" type="email" id="email" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" placeholder=" " required @if(isset($this->employeeId) || $this->lockPartyFields) disabled @endif />
                <label for="email" class="label">{{__('forms.email')}}</label>
                @error('form.party.email') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label for="about" class="peer appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ __('forms.aboutMyself') }}</label>
                <textarea id="about" name="about" class="textarea !text-gray-500 dark:!text-gray-400" placeholder="{{ __('forms.comment') }}"></textarea>
                @error('form.party.about') <p class="text-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</fieldset>
