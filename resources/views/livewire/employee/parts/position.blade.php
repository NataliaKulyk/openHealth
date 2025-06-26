<fieldset class="fieldset" x-data="{ employeeType: $wire.entangle('form.employeeType'), employeeTypePosition: @js($this->employeeTypePosition) }">
    <legend class="legend">
        <h2>{{ __('forms.position') }}</h2>
    </legend>

    {{-- This row uses the new design with 2 columns --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Employee Role --}}
        <div class="form-group">
            {{-- Using label from your colleague's version --}}
            <label for="employeeType" class="label">{{ __('forms.role') }} *</label>
            {{-- Using select with your working Livewire logic and colleague's classes --}}
            <select
                wire:model="form.employeeType"
                x-model="employeeType"
                id="employeeType"
                class="peer input-select @error('form.employeeType') input-error @enderror"
                required
            >
                <option value="" disabled selected>{{ __('forms.roleChoose') }}</option>
                @foreach($this->dictionaries['EMPLOYEE_TYPE'] as $k => $employeeTypeOption)
                    <option value="{{ $k }}">{{ $employeeTypeOption }}</option>
                @endforeach
            </select>
            @error('form.employeeType') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        {{-- Position (depends on role) --}}
        <div class="form-group">
            <label for="position" class="label">{{ __('forms.position') }} *</label>
            <select
                wire:model="form.position"
                id="position"
                class="peer input-select @error('form.position') input-error @enderror"
                required
            >
                <option value="" disabled selected>{{ __('forms.select_position') }}</option>
                {{-- Your working Alpine.js logic for dependent dropdowns --}}
                <template x-if="employeeType && employeeTypePosition[employeeType]">
                    <template x-for="(positionName, positionKey) in employeeTypePosition[employeeType]" :key="positionKey">
                        <option :value="positionKey" x-text="positionName"></option>
                    </template>
                </template>
            </select>
            @error('form.position') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- This row also uses the new design --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
        {{-- Start Date --}}
        <div class="form-group">
            <label for="startDate" class="label">{{ __('forms.startDateWork') }} *</label>
            <div class="relative">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none">
                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <input
                    wire:model="form.startDate"
                    datepicker
                    datepicker-format="yyyy-mm-dd"
                    datepicker-autohide
                    type="text"
                    id="startDate"
                    class="input datepicker-input ps-10 peer @error('form.startDate') input-error @enderror"
                    placeholder="{{__('forms.select_date')}}"
                    required
                    autocomplete="off"
                />
            </div>
            @error('form.startDate') <p class="text-error">{{$message}}</p> @enderror
        </div>

        {{-- Division --}}
        <div class="form-group">
            <label for="division" class="label">{{ __('forms.division') }}</label>
            <select
                wire:model="form.divisionUuid"
                id="division"
                class="peer input-select @error('form.divisionUuid') input-error @enderror"
            >
                <option value="">{{ __('forms.select_division') }}</option>
                {{-- Your temporary hardcoded option --}}
                <option value="b075f148-7f93-4fc2-b2ec-2d81b19a9b7b">Test division(mock)</option>
                {{-- Your original dynamic loop --}}
                @foreach($this->dictionaries['DIVISION'] ?? [] as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            @error('form.divisionUuid') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>
</fieldset>
