<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              {{-- Binding documents to Alpine, it will be re-used in the modal.
                Note that it's necessary for modal to work properly --}}
              x-data="{
                  specialities: $wire.entangle('form.doctor.specialities'),
                  openModal: false,
                  modalSpeciality: new Speciality(),
                  newSpeciality: false,
                  item: 0,
                  specDict: $wire.dictionaries['SPECIALITY_TYPE'],
                  levelDict: $wire.dictionaries['SPECIALITY_LEVEL'],
                  qualTypeDict: $wire.dictionaries['QUALIFICATION_TYPE'] // Словник для qualificationType
              }"
    >
        <legend class="legend">
            <h2>{{ __('forms.specialities') }}</h2>
        </legend>

        <table class="table-input w-full">
            <thead class="thead-input">
            <tr>
                <th class="th-input">{{ __('forms.speciality') }}</th>
                <th class="th-input">{{ __('forms.document_issued_by') }}</th>
                <th class="th-input">{{ __('forms.speciality_level') }}</th>
                <th class="th-input">{{ __('forms.specialityOfficio') }}</th>
                <th class="th-input">{{ __('forms.certificateNumber') }}</th>
                <th class="th-input">{{ __('forms.attestationDate') }}</th>
                <th class="th-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(speciality, index) in specialities" :key="index">
                <tr>
                    <td class="td-input" x-text="specDict[speciality.speciality]"></td>
                    <td class="td-input" x-text="speciality.attestationName"></td>
                    <td class="td-input" x-text="levelDict[speciality.level]"></td>
                    <td class="td-input" x-text="speciality.specialityOfficio"></td>
                    <td class="td-input" x-text="speciality.certificateNumber"></td>
                    <td class="td-input" x-text="speciality.attestationDate"></td>
                    <td class="td-input relative absolute right-0 top-full mt-2 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                        <x-dropdown-button
                            :editAction="'openModal = true; item = index; modalSpeciality = new Speciality(speciality); newSpeciality = false; close($refs.button)'"
                            :deleteAction="'specialities.splice(index, 1); close($refs.button)'"                        />
                    </td>
                </tr>
            </template>
            </tbody>
        </table>

        <div>
            <button @click="
                        openModal = true;
                        newSpeciality = true;
                        modalSpeciality = new Speciality();
                    "
                    @click.prevent
                    class="item-add my-5"
            >
                {{ __('forms.addSpeciality') }}
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
                                <span x-text="newSpeciality ? '{{ __('forms.addSpeciality') }}' : '{{ __('forms.edit') . ' ' . __('forms.speciality') }}'"></span>
                            </h3>

                            <form>
                                <div class="form-row-modal">
                                    <div>
                                        <label for="specialitySpeciality"
                                               class="label-modal">{{ __('forms.speciality') }}</label>
                                        <select x-model="modalSpeciality.speciality" id="specialitySpeciality"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.selectSpeciality')}}</option>
                                            @foreach($this->dictionaries['SPECIALITY_TYPE'] as $specValue => $specDescription)
                                                <option value="{{ $specValue }}">{{ $specDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="flex flex-col justify-end">
                                        <label class="inline-flex items-center mt-6">
                                            <input type="checkbox" x-model="modalSpeciality.specialityOfficio"
                                                   class="h-4 w-4 text-blue-600 dark:text-blue-500 bg-gray-100 dark:bg-gray-700 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 focus:ring-2">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ __('forms.specialityOfficio') }}</span>
                                        </label>
                                        <p class="text-red-500 dark:text-red-400 text-xs mt-1"
                                           x-show="modalSpeciality.specialityOfficio === null || modalSpeciality.specialityOfficio === undefined">
                                            {{ __('forms.field_empty') }}
                                        </p>
                                    </div>

                                    <div>
                                        <label for="specialityAttestationName"
                                               class="label-modal">{{ __('forms.document_issued_by') }}</label>
                                        <input x-model="modalSpeciality.attestationName" type="text"
                                               id="specialityAttestationName" class="input-modal" required>
                                    </div>

                                    <div>
                                        <label for="specialityLevel"
                                               class="label-modal">{{ __('forms.speciality_level') }}</label>
                                        <select x-model="modalSpeciality.level" id="specialityLevel"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.selectLevel')}}</option>
                                            @foreach($this->dictionaries['SPECIALITY_LEVEL'] as $levelValue => $levelDescription)
                                                <option value="{{ $levelValue }}">{{ $levelDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- ДОДАНО: Поле для Qualification Type --}}
                                    <div>
                                        <label for="specialityQualificationType"
                                               class="label-modal">{{ __('forms.qualificationType') }}</label>
                                        <select x-model="modalSpeciality.qualificationType" id="specialityQualificationType"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.selectQualificationType')}}</option>
                                            @foreach($this->dictionaries['SPEC_QUALIFICATION_TYPE'] as $qualTypeValue => $qualTypeDescription)
                                                <option value="{{ $qualTypeValue }}">{{ $qualTypeDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="specialityCertificateNumber"
                                               class="label-modal">{{ __('forms.certificateNumber') }}</label>
                                        <input x-model="modalSpeciality.certificateNumber" type="text"
                                               id="specialityCertificateNumber" class="input-modal">
                                    </div>
                                    <div>
                                        <label for="specialityAttestationDate"
                                               class="label-modal">{{ __('forms.attestationDate') }}</label>
                                        <input x-model="modalSpeciality.attestationDate" type="date"
                                               id="specialityAttestationDate" class="input-modal datepicker-input"
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
                                            @click="newSpeciality ? specialities.push(modalSpeciality) : specialities[item] = modalSpeciality; openModal = false"
                                            class="button-primary"
                                            :disabled="!(modalSpeciality.speciality && modalSpeciality.speciality.trim().length > 0 &&
                                                modalSpeciality.attestationName && modalSpeciality.attestationName.trim().length > 0 &&
                                                modalSpeciality.level && modalSpeciality.level.trim().length > 0 &&
                                                modalSpeciality.attestationDate && modalSpeciality.attestationDate.trim().length > 0 &&
                                                modalSpeciality.qualificationType && modalSpeciality.qualificationType.trim().length > 0)"
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
    class Speciality {
        speciality = '';
        specialityOfficio = '';
        level = '';
        attestationName = '';
        attestationDate = '';
        certificateNumber = '';
        qualificationType = '';

        constructor(obj = null) {
            if (obj) Object.assign(this, obj);
        }
    }
</script>
