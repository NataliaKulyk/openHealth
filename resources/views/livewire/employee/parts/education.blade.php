<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              x-data="{
                  educations: $wire.entangle('form.doctor.educations'),
                  openModal: false,
                  modalEducation: new Education(),
                  newEducation: false,
                  item: 0,
                  specDict: @js($this->dictionaries['SPECIALITY_TYPE']),
                  degreeDict: @js($this->dictionaries['EDUCATION_DEGREE']),
                  countryDict: @js($this->dictionaries['COUNTRY'])
              }"
    >
        <legend class="legend">
            <h2>{{ __('forms.education') }}</h2>
        </legend>

        <table class="table-input w-inherit">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="th-input">{{ __('forms.country') }}</th>
                <th scope="col" class="th-input">{{ __('forms.city') }}</th>
                <th scope="col" class="th-input">{{ __('forms.institutionName') }}</th>
                <th scope="col" class="th-input">{{ __('forms.speciality') }}</th>
                <th scope="col" class="th-input">{{ __('forms.degree') }}</th>
                <th scope="col" class="th-input">{{ __('forms.issuedDate') }}</th>
                <th scope="col" class="th-input">{{ __('forms.diplomaNumber') }}</th>
                <th scope="col" class="th-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(education, index) in educations" :key="index">
                <tr>
                    <td class="td-input" x-text="countryDict[education.country]"></td>
                    <td class="td-input" x-text="education.city"></td>
                    <td class="td-input" x-text="education.institutionName"></td>
                    <td class="td-input" x-text="specDict[education.speciality]"></td>
                    <td class="td-input" x-text="degreeDict[education.degree]"></td>
                    <td class="td-input" x-text="education.issuedDate"></td>
                    <td class="td-input" x-text="education.diplomaNumber"></td>
                    <td class="td-input">
                        <div
                            x-data="{ openDropdown: false }"
                            @keydown.escape.prevent.stop="openDropdown = false"
                            @focusin.window="!$refs.panel.contains($event.target) && (openDropdown = false)"
                            class="relative"
                        >
                            <button
                                x-ref="button"
                                @click="openDropdown = !openDropdown"
                                :aria-expanded="openDropdown"
                                type="button"
                                class="cursor-pointer"
                            >
                                <svg class="w-6 h-6 text-gray-800 dark:text-gray-200 svg-hover-action" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round" stroke-width="2" d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"></path>
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div
                                x-ref="panel"
                                x-show="openDropdown"
                                x-transition.origin.top.left
                                @click.outside="openDropdown = false"
                                class="dropdown-panel absolute"
                                style="left: -120%; display: none;"
                            >
                                <button
                                    @click.prevent="
                                        openModal = true;
                                        item = index;
                                        modalEducation = new Education(education);
                                        newEducation = false;
                                        openDropdown = false;
                                    "
                                    class="dropdown-button"
                                >
                                    {{ __('forms.edit') }}
                                </button>

                                <button
                                    @click.prevent="
                                        educations.splice(index, 1);
                                        openDropdown = false;
                                    "
                                    class="dropdown-button dropdown-delete"
                                >
                                    {{ __('forms.delete') }}
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            </template>
            </tbody>
        </table>

        <div>

            <button @click="
                        openModal = true;
                        newEducation = true;
                        modalEducation = new Education({ country: 'UA' });
                    "
                    @click.prevent
                    class="item-add my-5"
            >
                {{__('forms.addEducation')}}
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
                         class="relative flex min-h-screen items-center justify-start pl-72 p-4"
                    >
                        <div @click.stop
                             x-trap.noscroll.inert="openModal"
                             class="modal-content h-fit w-full max-w-6xl rounded-2xl shadow-lg bg-white"
                        >

                            <h3 class="modal-header" :id="$id('modal-title')">
                                <span x-text="newEducation ? '{{ __('forms.addEducation') }}' : '{{ __('forms.edit') . ' ' . __('forms.education') }}'"></span>
                            </h3>

                            <form>
                                <div class="form-row-modal">
                                    <div>
                                        <label for="educationCountry" class="label-modal">{{ __('forms.country') }}<span class="text-red-600"> *</span></label>
                                        <select x-model="modalEducation.country" id="educationCountry"
                                                class="input-modal" required>
                                            @foreach($this->dictionaries['COUNTRY'] as $countryValue => $countryDescription)
                                                <option value="{{ $countryValue }}">{{ $countryDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="educationCity" class="label-modal">{{ __('forms.city') }}<span class="text-red-600"> *</span></label>
                                        <input x-model="modalEducation.city" type="text" id="educationCity"
                                               class="input-modal" required>
                                    </div>
                                    <div>
                                        <label for="educationInstitutionName" class="label-modal">{{ __('forms.institutionName') }} <span class="text-red-600"> *</span></label>
                                        <input x-model="modalEducation.institutionName" type="text"
                                               id="educationInstitutionName" class="input-modal" required>
                                    </div>
                                    <div>
                                        <label for="educationSpeciality" class="label-modal">{{ __('forms.speciality') }} <span class="text-red-600"> *</span></label>
                                        <select x-model="modalEducation.speciality" id="educationSpeciality"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.select_speciality')}}</option>
                                            @foreach($this->dictionaries['SPECIALITY_TYPE'] as $specValue => $specDescription)
                                                <option value="{{ $specValue }}">{{ $specDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label for="educationDegree" class="label-modal">{{ __('forms.degree') }} <span class="text-red-600"> *</span></label>
                                        <select x-model="modalEducation.degree" id="educationDegree"
                                                class="input-modal" required>
                                            <option value="">{{__('forms.select_level')}}</option>
                                            @foreach($this->dictionaries['EDUCATION_DEGREE'] as $degreeValue => $degreeDescription)
                                                <option value="{{ $degreeValue }}">{{ $degreeDescription }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="relative">
                                        <svg class="svg-input absolute left-1 !top-2/3 transform -translate-y-1/2 pointer-events-none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7a2 2 0 0 1 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm5-6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z" clip-rule="evenodd"/>
                                        </svg>
                                        <label for="educationIssuedDate" class="label-modal">{{ __('forms.issuedDate') }}<span class="text-red-600"> *</span></label>
                                        <input x-model="modalEducation.issuedDate" type="text" name="educationIssuedDate" id="educationIssuedDate" class="input-modal datepicker-input" autocomplete="off">
                                    </div>
                                    <div>
                                        <label for="educationDiplomaNumber" class="label-modal">{{ __('forms.diplomaNumber') }} <span class="text-red-600"> *</span></label>
                                        <input x-model="modalEducation.diplomaNumber" type="text"
                                               id="educationDiplomaNumber" class="input-modal">
                                    </div>
                                </div>
                                <p class="text-sm text-gray-400 mb-2">{{ __('forms.form_required_note') }}</p>
                                <div class="mt-6 flex justify-between space-x-2">
                                    <button type="button"
                                            @click="openModal = false"
                                            class="button-minor"
                                    >
                                        {{__('forms.cancel')}}
                                    </button>

                                    <button @click.prevent
                                            @click="newEducation ? educations.push(modalEducation) : educations[item] = modalEducation; openModal = false"
                                            class="button-primary"
                                            :disabled="!(modalEducation.country && modalEducation.country.trim().length > 0 &&
                                                          modalEducation.city && modalEducation.city.trim().length > 0 &&
                                                          modalEducation.institutionName && modalEducation.institutionName.trim().length > 0 &&
                                                          modalEducation.speciality && modalEducation.speciality.trim().length > 0 &&
                                                          modalEducation.degree && modalEducation.degree.trim().length > 0 &&
                                                          modalEducation.diplomaNumber &&  modalEducation.diplomaNumber.trim().length > 0 &&
                                                          modalEducation.issuedDate && modalEducation.issuedDate.trim().length > 0)"
                                    >
                                        {{__('forms.save')}}
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
    class Education {
        country = '';
        city = '';
        institutionName = '';
        speciality = '';
        degree = '';
        issuedDate = '';
        diplomaNumber = '';

        constructor(obj = null) {
            if (obj) {
                Object.assign(this, obj);
            }
        }
    }
</script>
