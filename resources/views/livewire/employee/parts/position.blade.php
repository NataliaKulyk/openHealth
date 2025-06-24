<fieldset class="fieldset space-y-6 mt-8" x-data="{ employeeType: $wire.entangle('form.employeeType'), employeeTypePosition: @js($this->employeeTypePosition) }">
    <legend class="legend">
        <h2>{{ __('forms.position') ?? 'Посада' }}</h2>
    </legend>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        {{-- Employee Role --}}
        <div class="form-group">
            <label for="employeeType" class="label-main">{{__('forms.role')}} *</label>
            <select wire:model="form.employeeType" x-model="employeeType" id="employeeType" class="input-select peer @error('form.employeeType') input-error @enderror" required>
                <option value="" disabled>{{__('forms.roleChoose')}}</option>
                @foreach($this->dictionaries['EMPLOYEE_TYPE'] as $k => $employeeTypeOption)
                    <option value="{{ $k }}">{{ $employeeTypeOption }}</option>
                @endforeach
            </select>
            @error('form.employeeType') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- Position (depends on role) --}}
        <div class="form-group">
            <label for="position" class="label-main">{{__('forms.position')}} *</label>
            <select wire:model="form.position" id="position" class="input-select peer @error('form.position') input-error @enderror" required>
                <option value="" disabled>{{__('forms.select_position')}}</option>
                <template x-if="employeeType && employeeTypePosition[employeeType]">
                    <template x-for="(positionName, positionKey) in employeeTypePosition[employeeType]" :key="positionKey">
                        <option :value="positionKey" x-text="positionName"></option>
                    </template>
                </template>
            </select>
            @error('form.position') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- Start Date --}}
        <div class="form-group">
            <label for="startDate" class="label-main">{{__('forms.startDateWork')}} *</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <input wire:model="form.startDate" datepicker datepicker-format="yyyy-mm-dd" type="text" id="startDate" class="input datepicker-input peer ps-10 @error('form.startDate') input-error @enderror" placeholder="{{__('forms.select')}}" required />
            </div>
            @error('form.startDate') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- Division --}}
        <div class="form-group">
            <label for="division" class="label-main">{{__('forms.division')}}</label>
            <select wire:model="form.doctor.divisionUuid" id="division" class="input-select peer @error('form.doctor.divisionUuid') input-error @enderror">
                <option value="">{{__('forms.select')}}</option>
                @foreach($this->dictionaries['DIVISION'] ?? [] as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            @error('form.doctor.divisionUuid') <p class="text-error">{{ $message }}</p> @enderror
        </div>

    </div>
</fieldset>
