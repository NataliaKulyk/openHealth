<fieldset class="fieldset">
    <legend class="legend">
        {{ __('forms.main_information') }}
    </legend>

    {{-- Patient's full name --}}
    <div class="form-row-2">
        <div class="form-group group">
            <input type="text"
                   name="person"
                   id="person"
                   class="input-select peer"
                   placeholder=" "
                   autocomplete="off"
                   disabled
                   value="{{ $patientFullName }}"
            >

            <label for="person" class="label">
                {{ __('declarations.patient_full_name') }}
            </label>
        </div>
    </div>

    @if(count($employeesInfo) <= 1)
        {{-- Dr.'s full name --}}
        <div class="form-row-2">
            <div class="form-group group">
                <input type="text"
                       name="employee"
                       id="employee"
                       class="input-select peer"
                       value="{{ $employeesInfo[0]['fullName'] }} ({{ $this->dictionaries['POSITION'][$employeesInfo[0]['position']] }}) — {{ $employeesInfo[0]['divisionName'] }}"
                       disabled
                >

                <label for="employee" class="label">
                    {{ __('declarations.doctor_full_name') }}
                </label>
            </div>
        </div>
    @else
        {{-- Choose doctor --}}
        <div class="form-row-2">
            <div class="form-group group">
                <label class="label" for="employeeId">{{ __('declarations.doctor_full_name') }}</label>
                <select wire:model="form.employeeId"
                        id="employeeId"
                        name="employeeId"
                        class="input-select peer"
                        type="text"
                        required
                >
                    <option selected value="">
                        {{ __('forms.select') }}
                    </option>
                    @foreach($employeesInfo as $key => $employeeInfo)
                        <option value="{{ $employeeInfo['employeeId'] }}">
                            {{ $employeeInfo['fullName'] }}
                            ({{ $this->dictionaries['POSITION'][$employeeInfo['position']] }})
                            — {{ $employeeInfo['divisionName'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
</fieldset>
