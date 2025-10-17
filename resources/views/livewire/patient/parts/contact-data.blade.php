<fieldset class="fieldset">
    <legend class="legend">
        {{ __('patients.contact_data') }}
    </legend>

    {{-- Using Alpine to dynamically add and remove phone input fields --}}
    <div class="mb-4" x-data="{ phones: $wire.entangle('form.patient.phones') }">
        <template x-for="(phone, index) in phones">
            <div class="form-row-3 md:mb-0">
                <div class="form-group group">
                    <label :for="'phoneType-' + index" class="sr-only">{{ __('forms.type_mobile') }}</label>
                    <select x-model="phone.type" :id="'phoneType-' + index" class="input-select peer">
                        <option selected>{{ __('forms.type_mobile') }}</option>
                        @foreach($this->dictionaries['PHONE_TYPE'] as $key => $phoneType)
                            <option value="{{ $key }}">{{ $phoneType }}</option>
                        @endforeach
                    </select>

                    <p class="text-error">
                        {{ $errors->first('form.patient.phones.*.type') }}
                    </p>
                </div>

                <div class="form-group group">
                    <div class="phone-wrapper">
                        <input x-model="phone.number"
                               x-mask="+380999999999"
                               type="tel"
                               name="phoneNumber"
                               :id="'phoneNumber-' + index"
                               class="input with-leading-icon peer @error('form.patient.phones.*.number') input-error @enderror"
                               placeholder=" "
                        />
                        <label :for="'phoneNumber-' + index" class="wrapped-label">
                            {{ __('forms.phone_number') }}
                        </label>
                    </div>

                    <p class="text-error">
                        {{ $errors->first('form.patient.phones.*.number') }}
                    </p>
                </div>
                <template x-if="index == phones.length - 1 & index != 0">
                    {{-- Remove a phone if button is clicked --}}
                    <button @click="phones.pop(), index--" class="item-remove">
                        {{ __('forms.remove_phone') }}
                    </button>
                </template>
                <template x-if="index == phones.length - 1">
                    {{-- Add new phone if button is clicked --}}
                    <button @click="phones.push({ type: '', number: '' })"
                            class="item-add lg:justify-self-start"
                            :class="{ 'lg:justify-self-start': index > 0 }" {{-- Apply this style only if it's not a first phone group --}}
                    >
                        {{ __('forms.add_phone') }}
                    </button>
                </template>
            </div>
        </template>
    </div>

    <div class="form-row-3">
        <div class="form-group group">
            <input wire:model="form.patient.email"
                   type="email"
                   name="email"
                   id="email"
                   class="input peer @error('form.patient.email') input-error @enderror"
                   placeholder=" "
                   autocomplete="off"
            />
            <label for="email" class="label">
                {{ __('forms.email') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.email') }}
            </p>
        </div>

        <div class="form-group group">
            <input wire:model="form.patient.secret"
                   type="text"
                   name="secret"
                   id="secret"
                   class="input peer @error('form.patient.secret') input-error @enderror"
                   placeholder=" "
                   required
                   autocomplete="off"
            />
            <label for="secret" class="label">
                {{ __('patients.secret') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.secret') }}
            </p>
        </div>
    </div>
</fieldset>
