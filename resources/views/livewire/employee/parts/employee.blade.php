<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{__('forms.personalData')}}</h2>
    </legend>
    {{-- This form uses the new layout structure from your frontend developer --}}
    <div class="form">
        <div class="form-row-3">
            {{-- Last Name --}}
            <div class="form-group">
                <input wire:model="form.party.lastName" type="text" id="lastName" class="peer input @error('form.party.lastName') input-error @enderror" placeholder=" " required {{ $this->lockPartyFields ? 'disabled' : '' }} />
                <label for="lastName" class="label">{{__('forms.last_name')}}</label>
                @error('form.party.lastName') <p class="text-error">{{$message}}</p> @enderror
            </div>
            {{-- First Name --}}
            <div class="form-group">
                <input wire:model="form.party.firstName" type="text" id="firstName" class="peer input @error('form.party.firstName') input-error @enderror" placeholder=" " required {{ $this->lockPartyFields ? 'disabled' : '' }}/>
                <label for="firstName" class="label">{{__('forms.first_name')}}</label>
                @error('form.party.firstName') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>

        <div class="form-row-3">
            {{-- Second Name --}}
            <div class="form-group">
                <input wire:model="form.party.secondName" type="text" id="secondName" class="peer input @error('form.party.secondName') input-error @enderror" placeholder=" " {{ $this->lockPartyFields ? 'disabled' : '' }}/>
                <label for="secondName" class="label">{{__('forms.second_name')}}</label>
                @error('form.party.secondName') <p class="text-error">{{$message}}</p> @enderror
            </div>
            {{-- Gender --}}
            <div class="form-group">
                <select wire:model="form.party.gender" id="employeeGender" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400 @error('form.party.gender') input-error @enderror" required {{ $this->lockPartyFields ? 'disabled' : '' }}>
                    <option value="" disabled selected hidden></option>
                    @foreach($this->dictionaries['GENDER'] as $k => $gender)
                        <option value="{{ $k }}">{{ $gender }}</option>
                    @endforeach
                </select>
                <label for="employeeGender" class="label">{{ __('forms.gender') }}</label>
                @error('form.party.gender') <p class="text-error">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="form-row-3">
            {{-- Birth Date --}}
            <div class="form-group datepicker-wrapper relative w-full">
                <input wire:model="form.party.birthDate" type="text" id="birthDate" class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400 @error('form.party.birthDate') input-error @enderror" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false" {{ $this->lockPartyFields ? 'disabled' : '' }}/>
                <label for="birthDate" class="wrapped-label">{{__('forms.birth_date')}}</label>
                @error('form.party.birthDate') <p class="text-error">{{$message}}</p> @enderror
            </div>
            {{-- Working Experience --}}
            <div class="form-group">
                <input wire:model="form.party.workingExperience" type="number" id="workingExperience" class="peer input @error('form.party.workingExperience') input-error @enderror" placeholder=" " required min="0" {{ $this->lockPartyFields ? 'disabled' : '' }}/>
                <label for="workingExperience" class="label">{{__('forms.workingExperience')}}</label>
                @error('form.party.workingExperience') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>

        {{-- Tax ID Section --}}
        <div class="form-row-3" x-data="{ showNoTaxId: $wire.entangle('form.party.noTaxId'), isLocked: @js($this->lockPartyFields || !empty($this->employeeId)) }">
            <div class="form-group group relative z-0">
                <input wire:model="form.party.taxId" type="text" id="taxId" class="input peer @error('form.party.taxId') input-error @enderror" placeholder=" " required :disabled="showNoTaxId || isLocked" />
                <label for="taxId" class="label z-10" x-text="showNoTaxId ? '{{ __('forms.document_no_tax_id') }}' : '{{ __('forms.tax_id_number') }}'"></label>
                @error('form.party.taxId') <p class="text-error">{{ $message }}</p> @enderror
            </div>
            <div class="form-group group">
                <div class="mt-3">
                    <input x-model="showNoTaxId" type="checkbox" id="noTaxId" class="default-checkbox text-blue-500 focus:ring-blue-300" :disabled="isLocked">
                    <label for="noTaxId" class="ms-2 text-sm font-medium text-gray-500 dark:text-gray-300">{{ __('forms.no_tax_id') }}</label>
                </div>
            </div>
        </div>

        {{-- Phones Section --}}
        <div class="mb-4" x-data="{ phones: $wire.entangle('form.party.phones') }">
            <template x-for="(phone, index) in phones" :key="index">
                <div class="form-row-3 md:mb-0">
                    <div class="form-group">
                        <select x-model="phone.type" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400 @error('form.party.phones.' + index + '.type') input-error @enderror" required :disabled="$wire.lockPartyFields">
                            <option value="" disabled selected hidden></option>
                            @foreach($this->dictionaries['PHONE_TYPE'] as $k => $phoneType)
                                <option value="{{ $k }}">{{ $phoneType }}</option>
                            @endforeach
                        </select>
                        <label class="label">{{ __('forms.type_mobile') }}</label>
                        @error('form.party.phones.' + index + '.type') <p class="text-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-group phone-wrapper" x-data x-init="IMask($el.querySelector('input'), { mask: '+{380} (00) 000-00-00', lazy: false });">
                        <input type="tel" class="input with-leading-icon peer @error('form.party.phones.' + index + '.number') input-error @enderror" placeholder=" " required :disabled="$wire.lockPartyFields" />
                        <label class="wrapped-label">{{ __('forms.phone_number') }}</label>
                        @error('form.party.phones.' + index + '.number') <p class="text-error">{{ $message }}</p> @enderror
                    </div>
                    {{-- Add/Remove buttons --}}
                    <div class="flex items-center space-x-2">
                        <template x-if="index === phones.length - 1">
                            <button type="button" @click="phones.push({ type: '', number: '' })" class="item-add" :disabled="$wire.lockPartyFields">{{ __('forms.add_phone') }}</button>
                        </template>
                        <template x-if="index > 0">
                            <button type="button" @click="phones.splice(index, 1)" class="item-remove" :disabled="$wire.lockPartyFields">{{ __('forms.remove_phone') }}</button>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        {{-- Email --}}
        <div class="form-row-3">
            <div class="form-group">
                <input wire:model="form.party.email" type="email" id="email" class="peer input disabled:bg-gray-200" placeholder=" " required @if(isset($this->employeeId) || $this->lockPartyFields) disabled @endif/>
                <label for="email" class="label">{{__('forms.email')}}</label>
                @error('form.party.email') <p class="text-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- About Me --}}
        <div class="form-row-2">
            <div class="form-group">
                <label for="about" class="default-label text-gray-500 dark:text-gray-400">{{ __('forms.aboutMyself') }}</label>
                <textarea wire:model="form.party.aboutMyself" id="about" name="about" class="textarea disabled:bg-gray-200" placeholder="{{ __('forms.comment') }}" {{ $this->lockPartyFields ? 'disabled' : '' }}></textarea>
                @error('form.party.about') <p class="text-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>
</fieldset>
