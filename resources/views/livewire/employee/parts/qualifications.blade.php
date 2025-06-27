<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              {{-- Binding documents to Alpine, it will be re-used in the modal.
                Note that it's necessary for modal to work properly --}}
              x-data="{
                  qualifications: $wire.entangle('form.doctor.qualifications'),
                  openModal: false,
                  modalQualification: new Qualification(),
                  newQualification: false,
                  item: 0,
                  qualTypeDict: $wire.dictionaries['QUALIFICATION_TYPE'],
                  qualSpecDict: $wire.dictionaries['SPECIALITY_TYPE'],
              }"
    >
        <legend class="legend">
            <h2>{{ __('forms.qualifications') }}</h2>
        </legend>

        <table class="table-input w-inherit">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input">{{ __('forms.document_type') }}</th>
                <th scope="col" class="th-input">{{ __('forms.institutionName') }}</th>
                <th scope="col" class="th-input">{{ __('forms.speciality') }}</th>
                <th scope="col" class="th-input">{{ __('forms.certificateNumber') }}</th>
                <th scope="col" class="th-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(qualification, index) in qualifications" :key="index">
                <tr>
                    <td class="td-input" x-text="qualTypeDict[qualification.type]"></td>
                    <td class="td-input" x-text="qualification.institutionName"></td>
                    <td class="td-input" x-text="qualSpecDict[qualification.speciality]"></td>
                    <td class="td-input" x-text="qualification.certificateNumber"></td>
                    <td class="td-input relative absolute right-0 top-full mt-2 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                        <x-dropdown-button
                            :editAction="'openModal = true; item = index; modalQualification = new Qualification(qualification); newQualification = false; close($refs.button)'"
                            :deleteAction="'qualifications.splice(index, 1); close($refs.button)'"                        />
                    </td>
                </tr>
            </template>
            </tbody>
        </table>

        <div>

            <button @click="
                        openModal = true;
                        newQualification = true;
                        modalQualification = new Qualification();
                    "
                    @click.prevent
                    class="item-add my-5"
            >
                {{ __('forms.addQualification') }}
            </button>

            <template x-teleport="body">
                <div x-show="openModal"
                     style="display: none"
                     @keydown.escape.prevent.stop="openModal = false"
                     role="dialog"
                     aria-modal="true"
                     x-id="['modal-title']"
                     :aria-labelledby="$id('modal-title')"
                     class="modal"
                >

                    <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-black/25"></div>

                    <div x-show="openModal"
                         x-transition
                         @click="openModal = false"
                         class="relative flex min-h-screen items-center justify-center p-4"
                    >
                        <div @click.stop
                             x-trap.noscroll.inert="openModal"
                             class="modal-content h-fit w-full max-w-2xl rounded-2xl shadow-lg bg-white"
                        >

                            <h3 class="modal-header" :id="$id('modal-title')">
                                <span x-text="newQualification ? '{{ __('forms.addQualification') }}' : '{{ __('forms.edit') . ' ' . __('forms.qualification') }}'"></span>
                            </h3>

                            <form>
                                <div class="form-row-modal">
                                    <div>
                                        <label for="qualificationType"
                                               class="label-modal">{{ __('forms.qualificationType') }}</label>
                                        <select x-model="modalQualification.type" id="qualificationType"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.selectQualificationType')}}</option>
                                            @foreach($this->dictionaries['QUALIFICATION_TYPE'] as $typeValue => $typeDescription)
                                                <option value="{{ $typeValue }}">{{ $typeDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="qualificationInstitutionName"
                                               class="label-modal">{{ __('forms.institutionName') }}</label>
                                        <input x-model="modalQualification.institutionName" type="text"
                                               id="qualificationInstitutionName" class="input-modal" required>
                                    </div>
                                    <div>
                                        <label for="qualificationSpeciality"
                                               class="label-modal">{{ __('forms.speciality') }}</label>
                                        <select x-model="modalQualification.speciality" id="qualificationSpeciality"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.selectSpeciality')}}</option> {{-- ДОДАНО --}}
                                            @foreach($this->dictionaries['SPECIALITY_TYPE'] as $specValue => $specDescription)
                                                <option value="{{ $specValue }}">{{ $specDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="qualificationCertificateNumber"
                                               class="label-modal">{{ __('forms.certificateNumber') }}</label>
                                        <input x-model="modalQualification.certificateNumber" type="text"
                                               id="qualificationCertificateNumber" class="input-modal">
                                    </div>
                                    <div>
                                        <label for="qualificationIssuedDate"
                                               class="label-modal">{{ __('forms.issuedDate') }}</label>
                                        <input x-model="modalQualification.issuedDate" type="date"
                                               id="qualificationIssuedDate" class="input-modal datepicker-input"
                                               autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-between space-x-2">
                                    <button type="button"
                                            @click="openModal = false"
                                            class="button-minor"
                                    >
                                        {{ __('forms.cancel') }}
                                    </button>

                                    <button @click.prevent
                                            @click="newQualification ? qualifications.push(modalQualification) : qualifications[item] = modalQualification; openModal = false"
                                            class="button-primary"
                                            :disabled="!(modalQualification.type && modalQualification.type.trim().length > 0 &&
                                            modalQualification.institutionName && modalQualification.institutionName.trim().length > 0 &&
                                            modalQualification.speciality && modalQualification.speciality.trim().length > 0 &&
                                            modalQualification.issuedDate && modalQualification.issuedDate.trim().length > 0)"
                                    >
                                        {{ __('forms.save') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </fieldset>
</div>

<script>
    class Qualification {
        type = '';
        institutionName = '';
        speciality = '';
        certificateNumber = '';
        issuedDate = '';

        constructor(obj = null) {
            if (obj) Object.assign(this, obj);
        }
    }
</script>
