@use('App\Enums\Person\AuthenticationMethod')

<fieldset class="fieldset"
          x-data="{
              authenticationMethods: $wire.entangle('form.patient.authenticationMethods'),
              isIncapacitated: $wire.entangle('isIncapacitated'),

              get availableAuthMethods() {
                  if (this.isIncapacitated) {
                      return [
                          {
                              value: '{{ AuthenticationMethod::THIRD_PERSON->value }}',
                              label: '{{ __('forms.authentication') }} {{ AuthenticationMethod::THIRD_PERSON->label() }}'
                          }
                      ];
                  } else {
                      return [
                          {
                              value: '{{ AuthenticationMethod::OTP->value }}',
                              label: '{{ __('forms.authentication') }} {{ AuthenticationMethod::OTP->label() }}'
                          },
                          {
                              value: '{{ AuthenticationMethod::OFFLINE->value }}',
                              label: '{{ __('forms.authentication') }} {{ AuthenticationMethod::OFFLINE->label() }}'
                          }
                      ];
                  }
              }
          }"
>
    <legend class="legend">
        {{ __('forms.authentication') }}
    </legend>

    <div class="form-row-3">
        <div class="form-group group">
            <label for="relationType" class="sr-only">
                {{ __('forms.authentication') }}
            </label>
            <select x-model="authenticationMethods[0].type"
                    id="relationType"
                    class="input-select peer @error('form.patient.authenticationMethods.*.type') input-error @enderror"
                    required
            >
                <option selected value="">
                    {{ __('forms.select') }} {{ mb_strtolower(__('forms.authentication')) }} *
                </option>
                <template x-for="method in availableAuthMethods" :key="method.value">
                    <option :value="method.value" x-text="method.label"></option>
                </template>
            </select>

            <p class="text-error">
                {{ $errors->first('form.patient.authenticationMethods.*.type') }}
            </p>
        </div>
    </div>

    <template x-if="authenticationMethods[0]?.type === '{{ AuthenticationMethod::OTP->value }}'">
        <div class="form-row-3">
            <div class="form-group group">
                <input x-model="authenticationMethods[0].phoneNumber"
                       type="text"
                       x-mask="+380999999999"
                       name="phoneNumber"
                       id="phoneNumber"
                       class="input peer @error('form.patient.authenticationMethods.*.phoneNumber') input-error @enderror"
                       placeholder=" "
                       required
                       autocomplete="off"
                />
                <label for="phoneNumber" class="label">
                    {{ __('forms.phone_number') }}
                </label>

                <p class="text-error">
                    {{ $errors->first('form.patient.authenticationMethods.*.phoneNumber') }}
                </p>
            </div>
        </div>
    </template>

    <template x-if="authenticationMethods[0]?.type === '{{ AuthenticationMethod::THIRD_PERSON->value }}'">
        <div class="form-row-3">
            <div class="form-group group">
                <input x-model="authenticationMethods[0].alias"
                       type="text"
                       name="alias"
                       id="alias"
                       class="input peer @error('form.patient.authenticationMethods.*.alias') input-error @enderror"
                       placeholder=" "
                       required
                       autocomplete="off"
                />
                <label for="alias" class="label">
                    {{ __('patients.alias') }}
                </label>

                <p class="text-error">
                    {{ $errors->first('form.patient.authenticationMethods.*.alias') }}
                </p>
            </div>
        </div>
    </template>
</fieldset>
