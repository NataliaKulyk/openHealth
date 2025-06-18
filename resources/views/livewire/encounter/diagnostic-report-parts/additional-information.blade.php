<fieldset class="fieldset">
    <legend class="legend">
        {{ __('forms.additional_info') }}
    </legend>

    @if($context === 'encounter')
        {{-- Information source (doctor or patient) --}}
        <div class="flex gap-20 mb-8">
            <h2 class="default-p font-bold">{{ __('patients.information_source') }}</h2>
            {{-- Doctor --}}
            <div class="flex items-center">
                <input x-model.boolean="modalDiagnosticReport.primarySource"
                       id="performer"
                       type="radio"
                       value="true"
                       name="primarySource"
                       class="default-radio"
                       :checked="modalDiagnosticReport.primarySource === true"
                >
                <label for="performer" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                    {{ __('patients.performer') }}
                </label>
            </div>

            {{-- Patient --}}
            <div class="flex items-center">
                <input x-model.boolean="modalDiagnosticReport.primarySource"
                       id="patient"
                       type="radio"
                       value="false"
                       name="primarySource"
                       class="default-radio"
                       :checked="modalDiagnosticReport.primarySource === false"
                >
                <label for="patient" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                    {{ __('forms.patient') }}
                </label>
            </div>
        </div>

        {{-- When patient selected --}}
        <div x-show="modalDiagnosticReport.primarySource === false" x-transition>
            <div class="form-row-3">
                <div>
                    <label for="reportOrigin" class="label-modal">
                        {{ __('patients.source_link') }}
                    </label>
                    <select x-model="modalDiagnosticReport.reportOrigin.coding[0].code"
                            class="input-select peer"
                            id="reportOrigin"
                            type="text"
                            required
                    >
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach($this->dictionaries['eHealth/report_origins'] as $key => $reportOrigin)
                            <option value="{{ $key }}" wire:key="{{ $key }}">
                                {{ $reportOrigin }}
                            </option>
                        @endforeach
                    </select>

                    <p class="text-error text-xs"
                       x-show="!Object.keys($wire.dictionaries['eHealth/report_origins']).includes(modalDiagnosticReport.reportOrigin.coding[0].code)"
                    >
                        {{ __('forms.field_empty') }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    @if($context === 'diagnostic-report')
        <div class="form-row-3">
            <div class="form-group group">
                <select x-model="modalDiagnosticReport.division.identifier.value"
                        x-init="
                            {{-- Set division by default if only one exist --}}
                            if ({{ count($divisions) === 1 }}) {
                                modalDiagnosticReport.division.identifier.value = '{{ $divisions[0]['uuid'] }}';
                            }
                        "
                        id="divisionNames"
                        class="input-select peer"
                        type="text"
                >
                    <option value="" selected>
                        {{ __('forms.select') }} {{ mb_strtolower(__('patients.division_name')) }}
                    </option>
                    @foreach($divisions as $key => $division)
                        <option value="{{ $division['uuid'] }}">{{ $division['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    <div class="form-row-3">
        <div class="form-group group">
            <input x-model="modalDiagnosticReport.resultsInterpreter.text"
                   type="text"
                   name="resultsInterpreterText"
                   id="resultsInterpreterText"
                   class="input-select peer"
                   placeholder=" "
                   required
                   autocomplete="off"
            >
            <label for="resultsInterpreterText" class="label">
                {{ __('patients.the_doctor_who_interpreted_the_results') }}
            </label>
        </div>
    </div>

    {{-- Recorded by --}}
    <div class="form-row-3">
        <div class="form-group group">
            <input type="text"
                   name="recordedBy"
                   id="recordedBy"
                   class="input-select peer"
                   placeholder=" "
                   autocomplete="off"
                   disabled
                   value="{{ $employeeFullName }}"
            >

            <label for="recordedBy" class="label">
                {{ __('patients.doctor_submitting_a_report_to_the_system') }}
            </label>
        </div>
    </div>

    {{-- Issued datetime --}}
    <div class="form-row-3">
        <div class="form-group group">
            <div class="datepicker-wrapper">
                <input x-model="modalDiagnosticReport.issuedDate"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="text"
                       name="issuedDate"
                       id="issuedDate"
                       class="datepicker-input with-leading-icon input peer"
                       placeholder=" "
                       required
                       autocomplete="off"
                >
                <label for="issuedDate" class="wrapped-label">
                    {{ __('patients.date_and_time_of_entry') }}
                </label>
            </div>
        </div>

        <div class="form-group group !w-1/2" onclick="document.getElementById('issuedTime').showPicker()">
            <div class="relative flex items-center">
                <svg width="20" height="20" class="svg-input absolute left-2.5 pointer-events-none">
                    <use xlink:href="#svg-clock"></use>
                </svg>
                <input x-model="modalDiagnosticReport.issuedTime"
                       @input="$event.target.blur()"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="time"
                       name="issuedTime"
                       id="issuedTime"
                       class="input peer !pl-10"
                       autocomplete="off"
                       required
                >
            </div>
        </div>
    </div>

    {{-- Start effective period datetime --}}
    <div class="form-row-3">
        <div class="form-group group">
            <div class="datepicker-wrapper">
                <input x-model="modalDiagnosticReport.effectivePeriodStartDate"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="text"
                       name="effectivePeriodStartDate"
                       id="effectivePeriodStartDate"
                       class="datepicker-input with-leading-icon input peer"
                       placeholder=" "
                       required
                       autocomplete="off"
                >
                <label for="effectivePeriodStartDate" class="wrapped-label">
                    {{ __('patients.reception_start_date_and_time') }}
                </label>
            </div>
        </div>

        <div class="form-group group !w-1/2" onclick="document.getElementById('effectivePeriodStartTime').showPicker()">
            <div class="relative flex items-center">
                <svg width="20" height="20" class="svg-input absolute left-2.5 pointer-events-none">
                    <use xlink:href="#svg-clock"></use>
                </svg>
                <input x-model="modalDiagnosticReport.effectivePeriodStartTime"
                       @input="$event.target.blur()"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="time"
                       name="effectivePeriodStartTime"
                       id="effectivePeriodStartTime"
                       class="input peer !pl-10"
                       autocomplete="off"
                       required
                >
            </div>
        </div>
    </div>

    {{-- End effective period datetime --}}
    <div class="form-row-3">
        <div class="form-group group">
            <div class="datepicker-wrapper">
                <input x-model="modalDiagnosticReport.effectivePeriodEndDate"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="text"
                       name="effectivePeriodEndDate"
                       id="effectivePeriodEndDate"
                       class="datepicker-input with-leading-icon input peer"
                       placeholder=" "
                       required
                       autocomplete="off"
                >
                <label for="effectivePeriodEndDate" class="wrapped-label">
                    {{ __('patients.reception_end_date_and_time') }}
                </label>
            </div>
        </div>

        <div class="form-group group !w-1/2" onclick="document.getElementById('effectivePeriodEndTime').showPicker()">
            <div class="relative flex items-center">
                <svg width="20" height="20" class="svg-input absolute left-2.5 pointer-events-none">
                    <use xlink:href="#svg-clock"></use>
                </svg>
                <input x-model="modalDiagnosticReport.effectivePeriodEndTime"
                       @input="$event.target.blur()"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="time"
                       name="effectivePeriodEndTime"
                       id="effectivePeriodEndTime"
                       class="input peer !pl-10"
                       autocomplete="off"
                       required
                >
            </div>
        </div>
    </div>
</fieldset>
