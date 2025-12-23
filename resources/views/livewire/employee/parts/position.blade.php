<fieldset class="fieldset"
          :disabled="$wire.isPositionDataLocked ?? false"
          x-data="{
              employeeType: $wire.entangle('form.employeeType'),
              employeeTypePosition: @js($this->employeeTypePosition)
          }"
          x-init="$watch('employeeType', (value) => {
              $wire.set('form.position', '', false);
              document.getElementById('position').value = '';
          })"
>
    <legend class="legend">
        <h2>{{ __('forms.position') }}</h2>
    </legend>

    <div class="form-row-3">
        <div class="form-group">
            <select name="employeeType" id="employeeType" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" required wire:model="form.employeeType" x-model="employeeType">
                <option value="" disabled selected hidden>{{ __('forms.role_choose') }}</option>
                @foreach($this->dictionaries['EMPLOYEE_TYPE'] as $k => $employeeTypeOption)
                    <option value="{{ $k }}">{{ $employeeTypeOption }}</option>
                @endforeach
            </select>
            <label for="employeeType" class="label">{{ __('forms.role') }}</label>
            @error('form.employeeType') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-group">
            <select name="position" id="position" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" required wire:model="form.position">
                <option value="" disabled selected hidden>{{ __('forms.select_position') }}</option>
                <div x-show="employeeType && employeeTypePosition[employeeType]" x-cloak>
                    <template x-for="(positionName, positionKey) in employeeTypePosition[employeeType]" :key="positionKey">
                        <option :value="positionKey" x-text="positionName"></option>
                    </template>
                </div>
            </select>
            <label for="position" class="label">{{ __('forms.position') }}</label>
            @error('form.position') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-row-3">
        <div class="form-group datepicker-wrapper relative w-full">
            <input wire:model="form.startDate"
                   datepicker-format="{{ frontendDateFormat() }}"
                   type="text" name="startDate"
                   id="startDate" class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400"
                   placeholder=" "
                   required
                   datepicker-autohide
                   datepicker-button="false"/>
            <label for="startDate" class="wrapped-label">{{ __('forms.start_date_work') }}</label>
            @error('form.startDate') <p class="text-error">{{$message}}</p> @enderror
        </div>
        <div class="form-group">
            <select name="division" id="division" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" wire:model="form.divisionId">
                <option value="">{{ __('forms.select_division') }}</option>

                @foreach($this->divisions as $division)
                    <option value="{{ $division['id'] }}">{{ $division['name'] }}</option>
                @endforeach
            </select>
            <label for="division" class="label">{{ __('forms.division') }}</label>
            @error('form.divisionId') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- Start of email field --}}
        @if (!empty($partyUsers))
            <div class="form-group" x-transition wire:key="party-user-email-select">
                <select name="formEmail" id="formEmail"
                        class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                        required wire:model="formEmail">
                    <option value="" disabled>{{ __('forms.select_user_email') }}</option>
                    @foreach($partyUsers as $user)
                        <option value="{{ $user->email }}">{{ $user->email }}</option>
                    @endforeach
                </select>
                <label for="formEmail" class="label">{{ __('forms.email') }}</label>
                @error('formEmail') <p class="text-error">{{ $message }}</p> @enderror
            </div>
        @endif
        {{-- End of email filed --}}
    </div>
</fieldset>
