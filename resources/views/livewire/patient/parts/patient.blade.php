<fieldset class="fieldset">
    <legend class="legend">
        {{ __('patients.patient_information') }}
    </legend>

    <div class="form-row-3">
        <div class="form-group group">
            <input wire:model="form.patient.firstName"
                   type="text"
                   name="patientFirstName"
                   id="patientFirstName"
                   class="input peer @error('form.patient.firstName') input-error @enderror"
                   placeholder=" "
                   required
                   autocomplete="off"
            />
            <label for="patientFirstName" class="label">
                {{ __('forms.first_name') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.firstName') }}
            </p>
        </div>

        <div class="form-group group">
            <input wire:model="form.patient.lastName"
                   type="text"
                   name="patientLastName"
                   id="patientLastName"
                   class="input peer @error('form.patient.lastName') input-error @enderror"
                   placeholder=" "
                   required
                   autocomplete="off"
            />
            <label for="patientLastName" class="label">
                {{ __('forms.last_name') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.lastName') }}
            </p>
        </div>

        <div class="form-group group">
            <input wire:model="form.patient.secondName"
                   type="text"
                   name="patientSecondName"
                   id="patientSecondName"
                   class="input peer @error('form.patient.secondName') input-error @enderror"
                   placeholder=" "
                   autocomplete="off"
            />
            <label for="patientSecondName" class="label">
                {{ __('forms.second_name') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.secondName') }}
            </p>
        </div>
    </div>

    <div class="form-row-3">
        <div class="form-group group">
            <div class="datepicker-wrapper">
                <input wire:model="form.patient.birthDate"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="text"
                       name="birthDate"
                       id="birthDate"
                       class="datepicker-input with-leading-icon input peer @error('form.patient.birthDate') input-error @enderror"
                       placeholder=" "
                       required
                       autocomplete="off"
                />
                <label for="birthDate" class="wrapped-label">
                    {{ __('forms.birth_date') }}
                </label>
            </div>

            <p class="text-error">
                {{ $errors->first('form.patient.birthDate') }}
            </p>
        </div>

        <div class="form-group group">
            <input wire:model="form.patient.birthCountry"
                   type="text"
                   name="birthCountry"
                   id="birthCountry"
                   class="input peer @error('form.patient.birthCountry') input-error @enderror"
                   placeholder=" "
                   required
                   autocomplete="off"
            />
            <label for="birthCountry" class="label">
                {{ __('forms.birth_country') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.birthCountry') }}
            </p>
        </div>

        <div class="form-group group">
            <input wire:model="form.patient.birthSettlement"
                   type="text"
                   name="birthSettlement"
                   id="birthSettlement"
                   class="input peer @error('form.patient.birthSettlement') input-error @enderror"
                   placeholder=" "
                   required
                   autocomplete="off"
            />
            <label for="birthSettlement" class="label">
                {{ __('forms.birth_settlement') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.birthSettlement') }}
            </p>
        </div>
    </div>

    <div class="form-row-3">
        <div class="form-group">
            <select wire:model="form.patient.gender"
                    name="patientGender"
                    id="patientGender"
                    class="input-select peer
                    @error('form.patient.gender') input-error @enderror"
                    required
            >
                <option value="" disabled selected hidden>
                    {{ __('forms.select') }} *</option>
                @foreach($this->dictionaries['GENDER'] as $key => $gender)
                    <option value="{{ $key }}">{{ $gender }}</option>
                @endforeach
            </select>
            <label for="patientGender" class="label">
                {{ __('forms.gender') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.gender') }}
            </p>
        </div>

        <div class="form-group group">
            <input wire:model="form.patient.unzr"
                   type="text"
                   name="unzr"
                   id="unzr"
                   class="input peer @error('form.patient.unzr') input-error @enderror"
                   placeholder=" "
                   maxlength="14"
                   autocomplete="off"
            />
            <label for="unzr" class="label">
                {{ __('patients.unzr') }}
            </label>

            <p class="text-error">
                {{ $errors->first('form.patient.unzr') }}
            </p>
        </div>
    </div>
</fieldset>
