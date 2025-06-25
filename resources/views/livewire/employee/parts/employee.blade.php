<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{__('forms.personalData')}}</h2>
    </legend>
    <form class="form">
        <div class="form-row-3">
            <div class="form-group">
                <input type="text" name="lastName" id="lastName" class="peer input" placeholder=" " required />
                <label for="lastName" class="label">{{__('forms.last_name')}}</label>
                @error('form.party.lastName') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input type="text" name="firstName" id="firstName" class="peer input" placeholder=" " required />
                <label for="firstName" class="label">{{__('forms.first_name')}}</label>
                @error('form.party.firstName') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <input type="text" name="secondName" id="secondName" class="peer input" placeholder=" " required />
                <label for="secondName" class="label">{{__('forms.second_name')}}</label>
                @error('form.party.secondName') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <select name="employeeGender" id="employeeGender" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" required>
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
            <div class="form-group datepicker-wrapper relative w-full">
                <input type="text" name="birthDate" id="birthDate" class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                <label for="birthDate" class="wrapped-label">{{__('forms.birth_date')}}</label>
                @error('form.party.birthDate') <p class="text-error">{{$message}}</p> @enderror
            </div>
            <div class="form-group">
                <input type="number" name="workingExperience" id="workingExperience" class="peer input" placeholder=" " required />
                <label for="workingExperience" class="label">{{__('forms.workingExperience')}}</label>
                @error('form.party.workingExperience') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div
            class="form-row-3"
            x-data="{ showNoTaxId: $wire.entangle('form.party.noTaxId') }"
        >
            <div class="form-group group relative z-0">
                <input required id="taxId" type="text" name="taxId" maxlength="10" placeholder=" " wire:model="form.party.taxId" aria-describedby="{{ $errors->has('form.party.taxId') ? 'partyTaxIdErrorHelp' : '' }}" class="input {{ $errors->has('form.party.taxId') ? 'input-error border-red-500 focus:border-red-500' : ''}} peer"
                />
                @error('form.party.taxId')
                <p id="partyTaxIdErrorHelp" class="text-error">
                    {{ $message }}
                </p>
                @enderror
                <label
                    for="taxId"
                    class="label z-10"
                    x-text="showNoTaxId
                ? '{{ __('forms.document_no_tax_id') }}'
                : '{{ __('forms.number') . ' ' . __('forms.ipn') . ' / ' . __('forms.rnokpp') }}'"
                ></label>
            </div>
            <div class="form-group group">
                <div class="mt-3">
                    <input
                        type="checkbox"
                        id="noTaxId"
                        class="default-checkbox text-blue-500 focus:ring-blue-300"
                        x-model="showNoTaxId"
                        :checked="showNoTaxId"
                    >
                    <label for="noTaxId" class="ms-2 text-sm font-medium text-gray-500 dark:text-gray-300">
                        {{ __('forms.no_tax_id') }}
                    </label>
                </div>
            </div>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <select x-model="phone.type" id="phoneType-@{{ index }}" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" :class="{ 'input-error': $wire.errors.has('form.party.phones.' + index + '.type') }" required>
                    <option value="" disabled selected hidden></option>
                    @foreach($this->dictionaries['PHONE_TYPE'] as $k => $phoneType)
                        <option value="{{ $k }}">{{ $phoneType }}</option>
                    @endforeach
                </select>
                <label for="phoneType-@{{ index }}" class="label">{{ __('forms.type_mobile') }}</label>
                <p class="text-error"
                   x-text="$wire.errors.get('form.party.phones.' + index + '.type')"
                   x-show="$wire.errors.has('form.party.phones.' + index + '.type')"></p>
            </div>
            <div class="form-group phone-wrapper">
                <input type="text" name="phoneNumber" id="phoneNumber" class="peer input with-leading-icon" placeholder=" " required />
                <label for="phoneNumber" class="wrapped-label">{{ __('forms.phone') }}</label>
                @error('form.party.phoneNumber') <p class="text-error">{{ $message }}</p> @enderror
            </div>
            <button @click="
                   openModal = true;
                   newDocument = true;
                   modalDocument = new Doc();
                     "
                    @click.prevent
                    class="item-add my-5"
            >
                {{ __('forms.add_phone') }}
            </button>
        </div>
        <div class="form-row-3">
            <div class="form-group">
                <input type="text" name="email" id="email" class="peer input" placeholder=" " required />
                <label for="email" class="label">{{__('forms.email')}}</label>
                @error('form.party.email') <p class="text-error">{{$message}}</p> @enderror
            </div>
        </div>
        <div class="form-row-2">
            <div class="form-group">
                <label for="about" class="default-label text-gray-500 dark:text-gray-400">{{ __('forms.aboutMyself') }}</label>
                <textarea id="about" name="about" class="textarea" placeholder="{{ __('forms.comment') }}"></textarea>
                @error('form.party.about') <p class="text-error">{{ $message }}</p> @enderror
            </div>
        </div>
    </form>
</fieldset>
