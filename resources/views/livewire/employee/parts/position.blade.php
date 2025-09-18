<fieldset class="fieldset" x-data="{ employeeType: $wire.entangle('form.employeeType'), employeeTypePosition: @js($this->employeeTypePosition) }">
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
                <template x-if="employeeType && employeeTypePosition[employeeType]">
                    <template x-for="(positionName, positionKey) in employeeTypePosition[employeeType]" :key="positionKey">
                        <option :value="positionKey" x-text="positionName"></option>
                    </template>
                </template>
            </select>
            <label for="position" class="label">{{ __('forms.position') }}</label>
            @error('form.position') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-row-3">
        <div class="form-group datepicker-wrapper relative w-full">
            <input wire:model="form.startDate" type="text" name="startDate" id="startDate" class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
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
    </div>
</fieldset>
