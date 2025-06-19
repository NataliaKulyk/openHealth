<fieldset class="fieldset">
    <legend class="legend">
        {{ __('patients.main_information') }}
    </legend>

    {{-- Is referral available --}}
    <div>
        <div class="form-row-modal">
            <div class="form-group group">
                <input x-model="modalProcedure.isReferralAvailable"
                       @click="modalProcedure.isReferralAvailable = !modalProcedure.isReferralAvailable"
                       type="checkbox"
                       name="isDiagnosticReferralAvailable"
                       id="isDiagnosticReferralAvailable"
                       class="default-checkbox mb-1"
                       tabindex="-1"
                />
                <label class="default-p" for="isDiagnosticReferralAvailable">
                    {{ __('patients.referral_available') }}
                </label>
            </div>
        </div>

        {{-- When referral available --}}
        <template x-if="modalProcedure.isReferralAvailable">
            <div class="form-group group">
                <div class="form-row-modal" x-cloak>
                    <div>
                        <label for="referralType" class="label-modal">
                            {{ __('patients.requisition_type') }}
                        </label>
                        <select id="referralType"
                                class="input-modal"
                                type="text"
                                x-model="modalProcedure.referralType"
                                required
                        >
                            <option selected value="">{{ __('forms.select') }}</option>
                            <option value="electronic">{{ __('patients.electronic') }}</option>
                            <option value="paper">{{ __('patients.paper') }}</option>
                        </select>
                    </div>

                    {{-- Electronic referral --}}
                    <template x-if="modalProcedure.referralType === 'electronic'" x-transition>
                        <div class="form-group group">
                            <label for="eReferralNumber" class="label-modal">
                                {{ __('forms.number') }}
                            </label>
                            <input wire:model="form.encounter.episode.identifier.value"
                                   type="text"
                                   name="eReferralNumber"
                                   id="eReferralNumber"
                                   class="input-modal"
                                   placeholder=" "
                                   required
                                   autocomplete="off"
                            />
                        </div>
                    </template>
                </div>

                {{-- Paper referral --}}
                <template x-if="modalProcedure.referralType === 'paper'" x-transition>
                    <div>
                        <div class="form-row-modal">
                            <div class="form-group group">
                                <label for="requisition" class="label-modal">
                                    {{ __('forms.number') }}
                                </label>
                                <input x-model="modalProcedure.paperReferral.requisition"
                                       type="text"
                                       name="requisition"
                                       id="requisition"
                                       class="input-modal"
                                       placeholder=" "
                                       autocomplete="off"
                                >
                            </div>

                            <div class="form-group group">
                                <label for="requesterEmployeeName" class="label-modal">
                                    {{ __('patients.author') }}
                                </label>
                                <input x-model="modalProcedure.paperReferral.requesterEmployeeName"
                                       type="text"
                                       name="requesterEmployeeName"
                                       id="requesterEmployeeName"
                                       class="input-modal"
                                       placeholder=" "
                                       autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="form-row-modal">
                            <div class="form-group group">
                                <label for="requesterLegalEntityEdrpou" class="label-modal">
                                    {{ __('patients.edrpou_of_the_issuing_institution') }}
                                </label>
                                <input x-model="modalProcedure.paperReferral.requesterLegalEntityEdrpou"
                                       type="text"
                                       name="requesterLegalEntityEdrpou"
                                       id="requesterLegalEntityEdrpou"
                                       class="input-modal"
                                       placeholder=" "
                                       autocomplete="off"
                                       required
                                >

                                <p class="text-error text-xs"
                                   x-show="!modalProcedure.paperReferral.requesterLegalEntityEdrpou.trim()"
                                >
                                    {{ __('forms.field_empty') }}
                                </p>
                            </div>

                            <div class="form-group group">
                                <label for="requesterLegalEntityName" class="label-modal">
                                    {{ __('patients.name_of_the_institution_that_issued_it') }}
                                </label>
                                <input x-model="modalProcedure.paperReferral.requesterLegalEntityName"
                                       type="text"
                                       name="requesterLegalEntityName"
                                       id="requesterLegalEntityName"
                                       class="input-modal"
                                       placeholder=" "
                                       autocomplete="off"
                                       required
                                >

                                <p class="text-error text-xs"
                                   x-show="!modalProcedure.paperReferral.requesterLegalEntityName.trim()"
                                >
                                    {{ __('forms.field_empty') }}
                                </p>
                            </div>
                        </div>

                        <div class="form-row-modal">
                            <div class="form-group group">
                                <label for="serviceRequestDate" class="label-modal">
                                    {{ __('patients.date') }}
                                </label>
                                <div class="relative flex items-center">
                                    <svg width="20" height="20"
                                         class="svg-input absolute left-2.5 pointer-events-none"
                                    >
                                        <use xlink:href="#svg-calendar-week"></use>
                                    </svg>
                                    <input x-model="modalProcedure.paperReferral.serviceRequestDate"
                                           type="text"
                                           name="serviceRequestDate"
                                           id="serviceRequestDate"
                                           class="datepicker-input input-modal !pl-10"
                                           placeholder=" "
                                           required
                                           autocomplete="off"
                                    >
                                </div>

                                <p class="text-error text-xs"
                                   x-show="modalProcedure.paperReferral.serviceRequestDate.trim() === ''"
                                >
                                    {{ __('forms.field_empty') }}
                                </p>
                            </div>

                            <div class="form-group group">
                                <label for="note" class="label-modal">
                                    {{ __('patients.notes') }}
                                </label>
                                <input x-model="modalProcedure.paperReferral.note"
                                       type="text"
                                       name="note"
                                       id="note"
                                       class="input-modal"
                                       placeholder=" "
                                       autocomplete="off"
                                >
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        {{-- Category --}}
        <div class="form-row-modal">
            <div class="form-group group">
                <label for="category" class="label-modal">
                    {{ __('forms.category') }}
                </label>
                <select x-model="modalProcedure.category.coding[0].code"
                        id="category"
                        class="input-modal"
                        type="text"
                        required
                >
                    <option selected>{{ __('forms.select') }}</option>
                    @foreach($this->dictionaries['eHealth/procedure_categories'] as $key => $category)
                        <option value="{{ $key }}" wire:key="{{ $key }}">{{ $category }}</option>
                    @endforeach
                </select>

                <p class="text-error text-xs"
                   x-show="!Object.keys($wire.dictionaries['eHealth/procedure_categories']).includes(modalProcedure.category.coding[0].code)"
                >
                    {{ __('forms.field_empty') }}
                </p>
            </div>
        </div>

        {{-- Services --}}
        <div class="form-row-modal relative z-1">
            <div class="form-group group">
                <label for="serviceCode" class="label-modal">
                    {{ __('forms.services')}}
                </label>
                <x-select2 modelPath="modalProcedure.code.identifier.value"
                           dictionaryName="custom/services"
                           id="serviceCode"
                           class="input-modal"
                />

                <p class="text-error text-xs"
                   x-show="!$wire.dictionaries['custom/services'].some(item => item.id === modalProcedure.code.identifier.value)"
                >
                    {{ __('forms.field_empty') }}
                </p>
            </div>
        </div>

        {{-- Divisions --}}
        <div class="form-row-modal">
            <div class="form-group group">
                <label for="divisionNames" class="label-modal">
                    {{ __('patients.division_name')}}
                </label>
                <select x-model="modalProcedure.division.identifier.value"
                        x-init="
                            {{-- Set division by default if only one exist --}}
                            if ({{ count($divisions) === 1 }}) {
                                modalProcedure.division.identifier.value = '{{ $divisions[0]['uuid'] }}';
                            }
                        "
                        id="divisionNames"
                        class="input-modal"
                >
                    <option value="">{{ __('forms.select') }}</option>
                    @foreach($divisions as $key => $division)
                        <option value="{{ $division['uuid'] }}">{{ $division['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Outcome --}}
        <div class="form-row-modal">
            <div class="form-group group">
                <label for="outcome" class="label-modal">
                    {{ __('patients.outcome_result') }}
                </label>
                <select x-model="modalProcedure.outcome.coding[0].code"
                        id="outcome"
                        class="input-modal"
                        type="text"
                        required
                >
                    <option selected>{{ __('forms.select') }}</option>
                    @foreach($this->dictionaries['eHealth/procedure_outcomes'] as $key => $outcome)
                        <option value="{{ $key }}" wire:key="{{ $key }}">{{ $outcome }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
</fieldset>
