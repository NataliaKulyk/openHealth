<div x-show="showFilter">
    <div class="form-row-4">
        <div class="form-group phone-wrapper">
            <input {{--wire:model.defer="form.party.phones.{{$index}}.number"--}} type="tel" placeholder=" " class="peer input pl-10 with-leading-icon text-gray-500" x-model="phones[index].number" x-mask="+380999999999" :id="$id('phone', '_number' + index)" :class="{ 'input-error border-red-500': errors[legalEntityForm.phones.${index}.number] }" />
            <label for="phoneNumber" class="wrapped-label">{{ __('forms.phone') }}</label>
            @error('form.party.phoneNumber') <p class="text-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
            <input {{--wire:model="form.party.email"--}} type="email" id="email" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" placeholder=" " {{--@if(isset($this->employeeId) || $this->lockPartyFields) disabled @endif--}}/>
            <label for="email" class="label">{{__('forms.email')}}</label>
            @error('form.party.email') <p class="text-error">{{$message}}</p> @enderror
        </div>
    </div>
    <div class="form-row-4">
        <div class="form-group">
            <select name="employeeType" id="employeeType" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" {{--wire:model="form.employeeType"--}} x-model="employeeType">
                <option value="" disabled selected hidden>{{ __('forms.roleChoose') }}</option>
                {{--}}@foreach($this->dictionaries['EMPLOYEE_TYPE'] as $k => $employeeTypeOption)
                    <option value="{{ $k }}">{{ $employeeTypeOption }}</option>
                @endforeach--}}
            </select>
            <label for="employeeType" class="label">{{ __('forms.role') }}</label>
            @error('form.employeeType') <p class="text-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
            <select name="position" id="position" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400"  {{--wire:model="form.position"--}}>
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
    <div class="form-row-4">
        <div class="form-group">
            <select name="division" id="division" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" {{--wire:model="form.doctor.divisionUuid"--}}>
                <option value="" disabled selected hidden>{{ __('forms.select') }}</option>
                <option value="b075f148-7f93-4fc2-b2ec-2d81b19a9b7b">Test division(mock)</option>
            </select>
            <label for="division" class="label">{{ __('forms.division') }}</label>
            @error('form.doctor.divisionUuid') <p class="text-error">{{ $message }}</p> @enderror
        </div>
        <div class="form-group">
            <select name="status" id="status" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400"  {{--wire:model="form.status"--}}>
                <option value="" disabled selected hidden>{{ __('forms.select_position') }}</option>
            </select>
            <label for="status" class="label">{{ __('forms.status.label') }}</label>
            @error('form.position') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>
    <div class="py-4">
        <button wire:click.prevent="searchForPerson" class="flex items-center gap-2 button-primary">
            <svg width="16" height="16" id="svg-search" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                    d="M13.0667 14L8.86667 9.8C8.53333 10.0667 8.15 10.2778 7.71667 10.4333C7.28333 10.5889 6.82222 10.6667 6.33333 10.6667C5.12222 10.6667 4.09722 10.2472 3.25833 9.40833C2.41944 8.56944 2 7.54444 2 6.33333C2 5.12222 2.41944 4.09722 3.25833 3.25833C4.09722 2.41944 5.12222 2 6.33333 2C7.54444 2 8.56944 2.41944 9.40833 3.25833C10.2472 4.09722 10.6667 5.12222 10.6667 6.33333C10.6667 6.82222 10.5889 7.28333 10.4333 7.71667C10.2778 8.15 10.0667 8.53333 9.8 8.86667L14 13.0667L13.0667 14ZM6.33333 9.33333C7.16667 9.33333 7.875 9.04167 8.45833 8.45833C9.04167 7.875 9.33333 7.16667 9.33333 6.33333C9.33333 5.5 9.04167 4.79167 8.45833 4.20833C7.875 3.625 7.16667 3.33333 6.33333 3.33333C5.5 3.33333 4.79167 3.625 4.20833 4.20833C3.625 4.79167 3.33333 5.5 3.33333 6.33333C3.33333 7.16667 3.625 7.875 4.20833 8.45833C4.79167 9.04167 5.5 9.33333 6.33333 9.33333Z"
                    fill="currentColor"/>
            </svg>
            <span>{{ __('forms.employeeSearch') }}</span>
        </button>
    </div>
    </div>
