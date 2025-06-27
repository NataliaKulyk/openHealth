<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              x-data="{
                  scienceDegrees: $wire.entangle('form.doctor.scienceDegrees'),
                  openModal: false,
                  modalScienceDegree: new ScienceDegree(),
                  newScienceDegree: false,
                  item: 0,
                  degreeDict: @js($this->dictionaries['SCIENCE_DEGREE']),
                  specDict: @js($this->dictionaries['SPECIALITY_TYPE']),
                  countryDict: @js($this->dictionaries['COUNTRY']),
              }"
    >
        <legend class="legend">
            <h2>{{ __('forms.science_degree') }}</h2>
        </legend>

        <table class="table-input w-full">
            <thead class="thead-input">
            <tr>
                <th class="th-input">{{ __('forms.degree') }}</th>
                <th class="th-input">{{ __('forms.country') }}</th>
                <th class="th-input">{{ __('forms.city') }}</th>
                <th class="th-input">{{ __('forms.issuedDate') }}</th>
                <th class="th-input">{{ __('forms.institutionName') }}</th>
                <th class="th-input">{{ __('forms.speciality') }}</th>
                <th class="th-input">{{ __('forms.diplomaNumber') }}</th>
                <th class="th-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(scienceDegree, index) in scienceDegrees" :key="index">
                <tr>
                    <td class="td-input" x-text="degreeDict[scienceDegree.degree]"></td>
                    <td class="td-input" x-text="countryDict[scienceDegree.country]"></td>
                    <td class="td-input" x-text="scienceDegree.city"></td>
                    <td class="td-input" x-text="scienceDegree.issuedDate"></td>
                    <td class="td-input" x-text="scienceDegree.institutionName"></td>
                    <td class="td-input" x-text="specDict[scienceDegree.speciality]"></td>
                    <td class="td-input" x-text="scienceDegree.diplomaNumber"></td>
                    <td class="td-input relative absolute right-0 top-full mt-2 z-10 w-48 bg-white rounded divide-y divide-gray-100 shadow dark:bg-gray-700 dark:divide-gray-600">
                        <x-dropdown-button
                            :editAction="'openModal = true; item = index; modalScienceDegree = new ScienceDegree(scienceDegree); newScienceDegree = false; close($refs.button)'"
                            :deleteAction="'scienceDegrees.splice(index, 1); close($refs.button)'"                        />
                    </td>
                </tr>
            </template>
            </tbody>
        </table>

        <div>
            <button @click="
                        openModal = true;
                        newScienceDegree = true;
                        modalScienceDegree = new ScienceDegree({ country: 'UA' });
                    "
                    @click.prevent
                    class="item-add my-5"
            >
                {{ __('forms.addScienceDegree') }}
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
                                <span x-text="newScienceDegree ? '{{ __('forms.addScienceDegree') }}' : '{{ __('forms.edit') . ' ' . __('forms.science_degree') }}'"></span>
                            </h3>

                            <form>
                                <div class="form-row-modal">
                                    <div>
                                        <label for="scienceDegreeDegree"
                                               class="label-modal">{{ __('forms.degree') }}</label>
                                        <select x-model="modalScienceDegree.degree" id="scienceDegreeDegree"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.selectLevel')}}</option> {{-- ДОДАНО --}}
                                            @foreach($this->dictionaries['SCIENCE_DEGREE'] as $degreeValue => $degreeDescription)
                                                <option value="{{ $degreeValue }}">{{ $degreeDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="scienceDegreeCountry"
                                               class="label-modal">{{ __('forms.country') }}</label>
                                        <select x-model="modalScienceDegree.country" id="scienceDegreeCountry"
                                                class="input-modal" required>
                                            @foreach($this->dictionaries['COUNTRY'] as $countryValue => $countryDescription)
                                                <option value="{{ $countryValue }}">{{ $countryDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="scienceCity" class="label-modal">{{ __('forms.city') }}</label>
                                        <input x-model="modalScienceDegree.city" type="text" id="scienceCity"
                                               class="input-modal" required>
                                        <p class="text-error text-xs"
                                           x-show="!modalScienceDegree.city || modalScienceDegree.city.trim().length === 0">{{ __('forms.field_empty') }}</p>
                                    </div>
                                    <div>
                                        <label for="scienceDegreeIssuedDate"
                                               class="label-modal">{{ __('forms.issuedDate') }}</label>
                                        <input x-model="modalScienceDegree.issuedDate" type="date"
                                               id="scienceDegreeIssuedDate" class="input-modal datepicker-input"
                                               autocomplete="off" required>
                                    </div>
                                    <div>
                                        <label for="scienceDegreeInstitutionName"
                                               class="label-modal">{{ __('forms.institutionName') }}</label>
                                        <input x-model="modalScienceDegree.institutionName" type="text"
                                               id="scienceDegreeInstitutionName" class="input-modal" required>
                                    </div>
                                    <div>
                                        <label for="scienceDegreeSpeciality"
                                               class="label-modal">{{ __('forms.speciality') }}</label>
                                        <select x-model="modalScienceDegree.speciality" id="scienceDegreeSpeciality"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.selectSpeciality')}}</option> {{-- ДОДАНО --}}
                                            @foreach($this->dictionaries['SPECIALITY_TYPE'] as $specValue => $specDescription)
                                                <option value="{{ $specValue }}">{{ $specDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="scienceDegreeDiplomaNumber"
                                               class="label-modal">{{ __('forms.diplomaNumber') }}</label>
                                        <input x-model="modalScienceDegree.diplomaNumber" type="text"
                                               id="scienceDegreeDiplomaNumber" class="input-modal">
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
                                            @click="newScienceDegree ? scienceDegrees.push(modalScienceDegree) : scienceDegrees[item] = modalScienceDegree; openModal = false"
                                            class="button-primary"
                                            :disabled="!(modalScienceDegree.degree && modalScienceDegree.degree.trim().length > 0 &&
                                                modalScienceDegree.institutionName && modalScienceDegree.institutionName.trim().length > 0 &&
                                                modalScienceDegree.issuedDate && modalScienceDegree.issuedDate.trim().length > 0 &&
                                                modalScienceDegree.speciality && modalScienceDegree.speciality.trim().length > 0 &&
                                                modalScienceDegree.country && modalScienceDegree.country.trim().length > 0 &&
                                                modalScienceDegree.city && modalScienceDegree.city.trim().length > 0)"
                                    >
                                        {{ __('forms.save') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </template>
    </fieldset>
</div>

<script>
    class ScienceDegree {
        degree = '';
        country = '';
        city = '';
        issuedDate = '';
        institutionName = '';
        speciality = '';
        diplomaNumber = '';

        constructor(obj = null) {
            if (obj) {
                Object.assign(this, obj);
            }
        }
    }
</script>
