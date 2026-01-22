<div
    x-show="showLegalRepDrawer"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="translate-x-full"
    x-cloak
    class="fixed top-0 right-0 z-40 h-screen pt-20 p-4 overflow-y-auto transition-transform bg-white w-4/5 dark:bg-gray-800 shadow-2xl"
    x-data="{
        showResults: false,
        showDocumentDrawer: false,
        ]
    }"
    id="legal-representative-drawer"
    tabindex="-1"
>
    <h3 class="modal-header">
        {{ __('patients.add_legal_representative') }}
    </h3>

    <div class="mt-4">
        <div class="mb-8 flex items-center gap-1 font-semibold text-gray-900 dark:text-white">
            @icon('search-outline', 'w-4.5 h-4.5')
            <p>{{ __('patients.patient_search') }}</p>
        </div>

        <div x-data="{ showFilter: true, showAdditionalParams: false }">
            <div x-show="showFilter">
                <div class="form-row-3">
                    <div class="form-group group">
                        <input
                            type="text"
                            name="drawerFirstName"
                            id="drawerFirstName"
                            class="input peer"
                            placeholder=" "
                            autocomplete="off"
                        />
                        <label for="drawerFirstName" class="label">
                            {{ __('forms.first_name') }}
                        </label>
                    </div>

                    <div class="form-group group">
                        <input
                            type="text"
                            name="drawerLastName"
                            id="drawerLastName"
                            class="input peer"
                            placeholder=" "
                            autocomplete="off"
                        />
                        <label for="drawerLastName" class="label">
                            {{ __('forms.last_name') }}
                        </label>
                    </div>

                    <div class="form-group group">
                        <div class="datepicker-wrapper">
                            <input
                                datepicker-max-date="{{ now()->format('d.m.Y') }}"
                                datepicker-format="dd.mm.yyyy"
                                type="text"
                                name="drawerBirthDate"
                                id="drawerBirthDate"
                                class="datepicker-input with-leading-icon input peer"
                                placeholder=" "
                                autocomplete="off"
                            />
                            <label for="drawerBirthDate" class="wrapped-label">
                                {{ __('forms.birth_date') }}
                            </label>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="button"
                            class="flex items-center gap-2 button-minor mb-2"
                            @click.prevent="showAdditionalParams = !showAdditionalParams"
                    >
                        @icon('adjustments', 'w-4 h-4')
                        <span>{{ __('forms.additional_search_parameters') }}</span>
                    </button>

                    <div x-show="showAdditionalParams" x-transition x-cloak>
                        <div class="form-row-3">
                            <div class="form-group group">
                                <input
                                    type="text"
                                    name="drawerSecondName"
                                    id="drawerSecondName"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                />
                                <label for="drawerSecondName" class="label">
                                    {{ __('forms.second_name') }}
                                </label>
                            </div>

                            <div class="form-group group">
                                <input
                                    type="text"
                                    name="drawerTaxId"
                                    id="drawerTaxId"
                                    class="input peer"
                                    placeholder=" "
                                    maxlength="10"
                                    autocomplete="off"
                                />
                                <label for="drawerTaxId" class="label">
                                    {{ __('forms.rnokpp') }} ({{ __('forms.ipn') }})
                                </label>
                            </div>
                        </div>

                        <div class="form-row-3">
                            <div class="form-group group">
                                <input
                                    name="drawerPhoneNumber"
                                    id="drawerPhoneNumber"
                                    type="text"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                    x-mask="+380999999999"
                                />
                                <label for="drawerPhoneNumber" class="label">
                                    {{ __('forms.phone_number') }}
                                </label>
                            </div>

                            <div class="form-group group">
                                <input
                                    type="text"
                                    name="drawerBirthCertificate"
                                    id="drawerBirthCertificate"
                                    class="input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                />
                                <label for="drawerBirthCertificate" class="label">
                                    {{ __('forms.birth_certificate') }}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-9 mt-6 flex gap-2">
                <button type="button"
                        class="flex items-center gap-2 button-primary"
                        @click="showResults = true"
                >
                    @icon('search', 'w-4 h-4')
                    <span>{{ __('patients.search') }}</span>
                </button>
                <button type="button"
                        class="button-primary-outline-red"
                        @click="showResults = false; selectedPatient = null"
                >
                    {{ __('forms.reset_all_filters') }}
                </button>
            </div>
        </div>

        <fieldset class="p-4 sm:p-8 sm:pb-10 mt-6 border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700 max-w-full">
            <legend class="legend">
                {{ __('patients.confidant_person_documents_relationship') }}
            </legend>

            <div class="overflow-x-auto mb-4" x-show="legalRepresentatives.length > 0">
                <table class="table-input w-full">
                    <thead class="thead-input">
                        <tr>
                            <th scope="col" class="th-input">{{ __('forms.type') }}</th>
                            <th scope="col" class="th-input">{{ __('forms.number') }}</th>
                            <th scope="col" class="th-input">{{ __('forms.issued_by') }}</th>
                            <th scope="col" class="th-input">{{ __('forms.issued_at') }}</th>
                            <th scope="col" class="th-input">{{ __('forms.active_to') }}</th>
                            <th scope="col" class="th-input text-center">{{ __('forms.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(rep, index) in legalRepresentatives" :key="rep.id">
                            <tr>
                                <td class="td-input" x-text="rep.confirmDocType"></td>
                                <td class="td-input" x-text="rep.confirmDocNumber"></td>
                                <td class="td-input" x-text="rep.issuedBy || '-'"></td>
                                <td class="td-input" x-text="rep.issuedAt || '-'"></td>
                                <td class="td-input" x-text="rep.activeUntil || '-'"></td>
                                <td class="td-input text-center">
                                    <div class="relative"
                                         x-data="{ openDropdown: false }"
                                         @click.outside="openDropdown = false"
                                    >
                                        <button @click="openDropdown = !openDropdown"
                                                type="button"
                                                class="cursor-pointer"
                                        >
                                            @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-gray-200')
                                        </button>

                                        <div x-show="openDropdown"
                                             x-transition
                                             x-cloak
                                             class="absolute right-0 z-10 w-36 bg-white rounded shadow-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
                                        >
                                            <div class="py-1">
                                                <button type="button"
                                                        class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200"
                                                        @click="openDropdown = false"
                                                >
                                                    {{ __('forms.edit') }}
                                                </button>
                                                <button type="button"
                                                        class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-red-600 dark:text-red-400"
                                                        @click="legalRepresentatives.splice(index, 1); openDropdown = false"
                                                >
                                                    {{ __('forms.delete') }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <button type="button"
                    class="item-add"
                    @click.prevent="showDocumentDrawer = true"
            >
                {{ __('forms.add_document') }}
            </button>
        </fieldset>

        <div class="space-y-6 mt-6" x-show="showResults" x-transition x-cloak>

            <template x-for="patient in patients" :key="patient.id">
                <fieldset class="fieldset" :class="{ 'ring-2 ring-blue-500': selectedPatient?.id === patient.id }">
                    <legend class="legend" x-text="`${patient.lastName} ${patient.firstName} ${patient.secondName || ''}`"></legend>

                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div class="flex items-center flex-wrap gap-x-6 gap-y-2 text-sm text-gray-500 mt-2">
                            <span class="flex items-center gap-1.5" x-show="patient.birthDate">
                                <svg class="w-6 h-6 text-gray-800 dark:text-white" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                     viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                          d="M8 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H8z" />
                                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2"
                                          d="M16 2v4M8 2v4M3 10h18" />
                                </svg>
                                <span x-text="patient.birthDate"></span>
                            </span>

                            <span class="flex items-center gap-1.5 min-w-0" x-show="patient.phone">
                                @icon('tabler-phone', 'w-6 h-6 text-gray-800 dark:text-white')
                                <a :href="'tel:' + patient.phone"
                                   class="truncate hover:underline font-medium text-gray-900 dark:text-gray-200 text-base"
                                   x-text="patient.phone"
                                ></a>
                            </span>

                            <span class="flex items-center gap-1.5" x-show="patient.gender">
                                <template x-if="patient.gender === 'male'">
                                    <span class="flex items-center gap-1.5">
                                        @icon('men', 'w-6 h-6 text-gray-800 dark:text-white')
                                        <span>{{ __('patients.male') }}</span>
                                    </span>
                                </template>
                                <template x-if="patient.gender === 'female'">
                                    <span class="flex items-center gap-1.5">
                                        @icon('women', 'w-6 h-6 text-gray-800 dark:text-white')
                                        <span>{{ __('patients.female') }}</span>
                                    </span>
                                </template>
                            </span>
                        </div>

                        <button type="button"
                                class="button-primary text-sm"
                                @click="selectedPatient = patient; showDocumentDrawer = true"
                                x-show="!patient.isUnder18"
                        >
                            {{ __('forms.select') }}
                        </button>
                    </div>

                    <div class="flow-root mt-4">
                        <div class="max-w-screen-xl">
                            <table class="table-input w-full table-auto">
                                <thead class="thead-input">
                                <tr>
                                    <th scope="col" class="th-input">{{ __('forms.city') }}</th>
                                    <th scope="col" class="th-input">{{ __('forms.rnokpp') }}</th>
                                    <th scope="col" class="th-input">{{ __('patients.birth_certificate') }}</th>
                                    <th scope="col" class="th-input">{{ __('forms.status.label') }}</th>
                                </tr>
                                </thead>

                                <tbody>
                                <tr>
                                    <td class="td-input whitespace-nowrap overflow-hidden text-ellipsis align-top font-bold text-gray-900 dark:text-white"
                                        x-text="patient.birthSettlement || '-'">
                                    </td>
                                    <td class="td-input whitespace-nowrap overflow-hidden text-ellipsis align-top font-bold text-gray-900 dark:text-white"
                                        x-text="patient.taxId || '-'">
                                    </td>
                                    <td class="td-input whitespace-nowrap overflow-hidden text-ellipsis align-top font-bold text-gray-900 dark:text-white"
                                        x-text="patient.birthCertificate || '-'">
                                    </td>
                                    <td class="td-input whitespace-nowrap align-top">
                                        <span :class="patient.statusColor + ' px-2 py-0.5 rounded text-xs'"
                                              x-text="patient.statusLabel">
                                        </span>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div x-show="patient.isUnder18" x-cloak class="mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20">
                        <div class="flex items-center gap-2">
                            @icon('alert-circle', 'w-5 h-5 text-red-700 dark:text-red-400')
                            <p class="font-semibold text-red-700 dark:text-red-400">
                                {{ __('patients.age_insufficient_for_legal_representative') }}
                            </p>
                        </div>
                    </div>
                </fieldset>
            </template>

            <template x-if="patients.length === 0">
                <fieldset class="fieldset mx-auto">
                    <legend class="legend relative -top-5">@icon('nothing-found', 'w-28 h-28')</legend>
                    <div class="p-4 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-start mb-4">
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 mt-0.5">
                                @icon('alert-circle', 'w-5 h-5 text-blue-500 dark:text-blue-400 mr-3 mt-1')
                            </div>
                            <div class="flex-1">
                                <p class="font-bold text-blue-800 dark:text-blue-300">
                                    {{ __('forms.nothing_found') }}
                                </p>
                                <p class="text-sm text-blue-600 dark:text-blue-400">
                                    {{ __('forms.changing_search_parameters') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </template>

        </div>

        {{-- Document Drawer Overlay --}}
        <div x-show="showDocumentDrawer"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             x-cloak
             @click="showDocumentDrawer = false"
             class="fixed inset-0 bg-gray-900/50"
             style="z-index: 55;"
        ></div>

        {{-- Document Drawer --}}
        <div x-show="showDocumentDrawer"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             x-cloak
             class="fixed top-0 right-0 h-screen pt-16 bg-white dark:bg-gray-800 shadow-2xl"
             style="z-index: 60; width: calc(80% - 35px);"
             id="add-document-drawer"
             tabindex="-1"
        >
            <div class="border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-6 py-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                    {{ __('forms.add_document') }}
                </h2>
            </div>

            <div class="overflow-y-auto p-6 bg-white dark:bg-gray-800" style="height: calc(100% - 70px);">

                <div class="mt-4">
                    <div class="form-row-3">
                        <div class="form-group group">
                            <select
                                name="documentType"
                                id="documentType"
                                class="input-select peer"
                                x-model="newDocument.type"
                                @change="newDocument.typeLabel = $event.target.options[$event.target.selectedIndex].text"
                                required
                            >
                                <option value="">{{ __('forms.select') }}</option>
                                <option value="birth_certificate">{{ __('patients.documents.birth_certificate') }}</option>
                                <option value="confidant_certificate">{{ __('patients.documents.confidant_certificate') }}</option>
                            </select>
                            <label for="documentType" class="label">
                                {{ __('forms.document_type') }}
                            </label>
                        </div>

                        <div class="form-group group">
                            <input
                                type="text"
                                name="documentNumber"
                                id="documentNumber"
                                class="input peer"
                                placeholder=" "
                                autocomplete="off"
                                x-model="newDocument.number"
                                required
                            />
                            <label for="documentNumber" class="label">
                                {{ __('forms.document_number') }}
                            </label>
                        </div>

                        <div class="form-group group">
                            <input
                                type="text"
                                name="documentIssuedBy"
                                id="documentIssuedBy"
                                class="input peer"
                                placeholder=" "
                                autocomplete="off"
                                x-model="newDocument.issuedBy"
                                required
                            />
                            <label for="documentIssuedBy" class="label">
                                {{ __('forms.issued_by') }}
                            </label>
                        </div>
                    </div>

                    <div class="form-row-3">
                        <div class="form-group group">
                            <div class="datepicker-wrapper">
                                <input
                                    datepicker-max-date="{{ now()->format('d.m.Y') }}"
                                    datepicker-format="dd.mm.yyyy"
                                    type="text"
                                    name="documentIssueDate"
                                    id="documentIssueDate"
                                    class="datepicker-input with-leading-icon input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                    x-model="newDocument.issuedAt"
                                    required
                                />
                                <label for="documentIssueDate" class="wrapped-label">
                                    {{ __('forms.issued_date') }}
                                </label>
                            </div>
                        </div>

                        <div class="form-group group">
                            <div class="datepicker-wrapper">
                                <input
                                    datepicker-format="dd.mm.yyyy"
                                    type="text"
                                    name="documentExpiryDate"
                                    id="documentExpiryDate"
                                    class="datepicker-input with-leading-icon input peer"
                                    placeholder=" "
                                    autocomplete="off"
                                    x-model="newDocument.expiryDate"
                                />
                                <label for="documentExpiryDate" class="wrapped-label">
                                    {{ __('forms.expiry_date') }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div x-show="newDocument.type === 'confidant_certificate'" x-transition x-cloak class="mt-6 space-y-6">
                        <div class="pb-4" x-data="{ fileName: '' }">
                            <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('forms.document_scans') }}
                            </label>
                            <div class="file-input-wrapper">
                                <label for="confidantCertificateScans" class="file-input-button">
                                    {{ __('patients.select_file') }}
                                </label>
                                <span class="file-input-text" x-text="fileName || '{{ __('patients.file_not_selected') }}'"></span>
                                <input type="file"
                                       class="hidden"
                                       id="confidantCertificateScans"
                                       accept=".jpeg,.jpg"
                                       multiple
                                       @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' {{ __('forms.files_selected') }}' : ($event.target.files[0]?.name || '')"
                                />
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                {{ __('forms.max_file_size_and_format') }}
                            </p>
                        </div>
                    </div>

                    <div x-show="newDocument.type === 'birth_certificate'" x-transition x-cloak class="mt-6 space-y-6">
                        <div class="pb-4" x-data="{ fileName: '' }">
                            <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('forms.birth_certificate_scans') }}
                            </label>
                            <div class="file-input-wrapper">
                                <label for="birthCertificateScans" class="file-input-button">
                                    {{ __('patients.select_file') }}
                                </label>
                                <span class="file-input-text" x-text="fileName || '{{ __('patients.file_not_selected') }}'"></span>
                                <input type="file"
                                       class="hidden"
                                       id="birthCertificateScans"
                                       accept=".jpeg,.jpg"
                                       multiple
                                       @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' {{ __('forms.files_selected') }}' : ($event.target.files[0]?.name || '')"
                                />
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                {{ __('forms.max_file_size_and_format') }}
                            </p>
                        </div>

                        <div class="pb-4" x-data="{ fileName: '' }">
                            <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('forms.apostille_scans') }}
                            </label>
                            <div class="file-input-wrapper">
                                <label for="apostilleScans" class="file-input-button">
                                    {{ __('patients.select_file') }}
                                </label>
                                <span class="file-input-text" x-text="fileName || '{{ __('patients.file_not_selected') }}'"></span>
                                <input type="file"
                                       class="hidden"
                                       id="apostilleScans"
                                       accept=".jpeg,.jpg"
                                       multiple
                                       @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' {{ __('forms.files_selected') }}' : ($event.target.files[0]?.name || '')"
                                />
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                {{ __('forms.max_file_size_and_format') }}
                            </p>
                        </div>

                        <div class="pb-4" x-data="{ fileName: '' }">
                            <label class="block mb-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('forms.translation_scans') }}
                            </label>
                            <div class="file-input-wrapper">
                                <label for="translationScans" class="file-input-button">
                                    {{ __('patients.select_file') }}
                                </label>
                                <span class="file-input-text" x-text="fileName || '{{ __('patients.file_not_selected') }}'"></span>
                                <input type="file"
                                       class="hidden"
                                       id="translationScans"
                                       accept=".jpeg,.jpg"
                                       multiple
                                       @change="fileName = $event.target.files.length > 1 ? $event.target.files.length + ' {{ __('forms.files_selected') }}' : ($event.target.files[0]?.name || '')"
                                />
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-300">
                                {{ __('forms.max_file_size_and_format') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-start gap-3">
                        <button
                            type="button"
                            class="button-minor"
                            @click="showDocumentDrawer = false; newDocument.type = ''; newDocument.typeLabel = ''; newDocument.number = ''; newDocument.issuedBy = ''; newDocument.issuedAt = ''; newDocument.expiryDate = '';"
                        >
                            {{ __('forms.cancel') }}
                        </button>
                        <button
                            type="button"
                            class="button-primary"
                            @click="addLegalRepresentative(); showDocumentDrawer = false;"
                            :disabled="!newDocument.type || !newDocument.number || !newDocument.issuedBy || !newDocument.issuedAt"
                        >
                            {{ __('forms.add_document') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
