<fieldset class="fieldset">
    <legend class="legend">
        {{ __('forms.additional_info') }}
    </legend>

    {{-- Information source (performer of other source) --}}
    <div class="flex gap-20 md:mb-5 mb-4">
        <h2 class="default-p font-bold">{{ __('patients.information_source') }}</h2>
        <div class="flex items-center">
            <input @change="modalProcedure.primarySource = true"
                   x-model.boolean="modalProcedure.primarySource"
                   id="performer"
                   type="radio"
                   value="true"
                   name="primarySource"
                   class="default-radio"
                   :checked="modalProcedure.primarySource === true"
            >
            <label for="performer" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                {{ __('patients.performer') }}
            </label>
        </div>

        <div class="flex items-center">
            <input @change="modalProcedure.primarySource = false"
                   x-model.boolean="modalProcedure.primarySource"
                   id="patient"
                   type="radio"
                   value="false"
                   name="primarySource"
                   class="default-radio"
                   :checked="modalProcedure.primarySource === false"
            >
            <label for="patient" class="ms-2 text-sm font-medium text-gray-900 dark:text-gray-300">
                {{ __('patients.other_source') }}
            </label>
        </div>
    </div>

    {{-- When the performer is chosen --}}
    <div x-show="modalProcedure.primarySource === true" class="form-row-modal">
        <div class="form-group group">
            <label for="procedurePerformer" class="label-modal">
                {{ __('patients.doctor_who_performed') }}
            </label>
            <input type="text"
                   name="procedurePerformer"
                   id="procedurePerformer"
                   class="input-modal"
                   placeholder=" "
                   autocomplete="off"
                   disabled
                   value="{{ $employeeFullName }}"
            >
        </div>
    </div>

    {{-- When the other source is choosen  --}}
    <div x-show="modalProcedure.primarySource === false">
        <div class="form-row-modal">
            <div>
                <label for="reportOrigin" class="label-modal">
                    {{ __('patients.source_link') }}
                </label>
                <select class="input-modal"
                        x-model="modalProcedure.reportOrigin.coding[0].code"
                        id="reportOrigin"
                        type="text"
                        required
                >
                    <option selected>{{ __('forms.select') }}</option>
                    @foreach($this->dictionaries['eHealth/report_origins'] as $key => $reportOrigin)
                        <option value="{{ $key }}" wire:key="{{ $key }}">
                            {{ $reportOrigin }}
                        </option>
                    @endforeach
                </select>

                <p class="text-error text-xs"
                   x-show="!Object.keys($wire.dictionaries['eHealth/report_origins']).includes(modalProcedure.reportOrigin.coding[0].code)"
                >
                    {{ __('forms.field_empty') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Start effective period datetime --}}
    <div class="form-row-3">
        <div class="form-group group">
            <label for="performedPeriodStartDate" class="label-modal">
                {{ __('patients.procedure_start_date_and_time') }}
            </label>
            <div class="relative flex items-center">
                <svg width="20" height="20"
                     class="svg-input absolute left-2.5 pointer-events-none"
                >
                    <use xlink:href="#svg-calendar-week"></use>
                </svg>

                <input x-model="modalProcedure.performedPeriodStartDate"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="text"
                       name="performedPeriodStartDate"
                       id="performedPeriodStartDate"
                       class="datepicker-input input-modal !pl-10"
                       placeholder=" "
                       required
                       autocomplete="off"
                >

            </div>
        </div>

        <div class="w-1/2 mt-7" onclick="document.getElementById('performedPeriodStartTime').showPicker()">
            <div class="relative flex items-center">
                <svg width="20" height="20" class="svg-input absolute left-2.5 pointer-events-none">
                    <use xlink:href="#svg-clock"></use>
                </svg>
                <input x-model="modalProcedure.performedPeriodStartTime"
                       @input="$event.target.blur()"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="time"
                       name="performedPeriodStartTime"
                       id="performedPeriodStartTime"
                       class="input-modal !pl-10"
                       autocomplete="off"
                       required
                >
            </div>
        </div>
    </div>

    {{-- End effective period datetime --}}
    <div class="form-row-3">
        <div class="form-group group">
            <label for="performedPeriodEndDate" class="label-modal">
                {{ __('patients.procedure_end_date_and_time') }}
            </label>
            <div class="relative flex items-center">
                <svg width="20" height="20"
                     class="svg-input absolute left-2.5 pointer-events-none"
                >
                    <use xlink:href="#svg-calendar-week"></use>
                </svg>

                <input x-model="modalProcedure.performedPeriodEndDate"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="text"
                       name="performedPeriodEndDate"
                       id="performedPeriodEndDate"
                       class="datepicker-input input-modal !pl-10"
                       placeholder=" "
                       required
                       autocomplete="off"
                >
            </div>
        </div>

        <div class="w-1/2 mt-7" onclick="document.getElementById('performedPeriodEndTime').showPicker()">
            <div class="relative flex items-center">
                <svg width="20" height="20" class="svg-input absolute left-2.5 pointer-events-none">
                    <use xlink:href="#svg-clock"></use>
                </svg>
                <input x-model="modalProcedure.performedPeriodEndTime"
                       @input="$event.target.blur()"
                       datepicker-max-date="{{ now()->format('Y-m-d') }}"
                       type="time"
                       name="performedPeriodEndTime"
                       id="performedPeriodEndTime"
                       class="input-modal !pl-10"
                       autocomplete="off"
                       required
                >
            </div>
        </div>
    </div>

    {{-- Note --}}
    <div class="form-row">
        <div>
            <label for="note" class="label-modal">
                {{ __('patients.notes') }}
            </label>
            <div>
                <textarea rows="4"
                          x-model="modalProcedure.note"
                          id="note"
                          name="note"
                          class="textarea"
                          placeholder="{{ __('patients.write_comment_here') }}"
                ></textarea>
            </div>
        </div>
    </div>
</fieldset>
