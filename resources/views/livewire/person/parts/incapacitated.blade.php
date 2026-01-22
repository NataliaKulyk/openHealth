<fieldset class="fieldset"
          x-data="{
              isIncapacitated: $wire.entangle('isIncapacitated'),
              showLegalRepDrawer: false,
              legalRepresentatives: [],
              selectedPatient: null,
              newDocument: {
                  type: '',
                  typeLabel: '',
                  number: '',
                  issuedBy: '',
                  issuedAt: '',
                  expiryDate: ''
              },
              addLegalRepresentative() {
                  if (this.selectedPatient && this.newDocument.type) {
                      this.legalRepresentatives.push({
                          id: Date.now(),
                          fullName: this.selectedPatient.lastName + ' ' + this.selectedPatient.firstName.charAt(0) + '.' + (this.selectedPatient.secondName ? this.selectedPatient.secondName.charAt(0) + '.' : ''),
                          gender: this.selectedPatient.gender === 'male' ? 'Чоловік' : 'Жінка',
                          taxId: this.selectedPatient.taxId,
                          unzr: this.selectedPatient.unzr || '',
                          documentType: 'Паспорт',
                          documentNumber: this.selectedPatient.documentNumber || 'СН...',
                          phoneType: 'Мобільний',
                          phone: this.selectedPatient.phone,
                          activeUntil: this.newDocument.expiryDate,
                          confirmDocType: this.newDocument.typeLabel,
                          confirmDocNumber: this.newDocument.number
                      });
                      this.resetForm();
                  }
              },
              resetForm() {
                  this.selectedPatient = null;
                  this.newDocument = {
                      type: '',
                      typeLabel: '',
                      number: '',
                      issuedBy: '',
                      issuedAt: '',
                      expiryDate: ''
                  };
                  this.showLegalRepDrawer = false;
              }
          }"
>
    <legend class="legend flex items-baseline gap-2">
        <x-checkbox class="default-checkbox mb-2"
                    x-model="isIncapacitated"
                    id="isIncapacitated"
        />
        {{ __('patients.incapacitated') }}
    </legend>

    <div x-show="isIncapacitated" x-cloak x-transition>

        <div class="mb-6" x-show="legalRepresentatives.length > 0">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                {{ __('patients.legal_representatives') }}
            </h3>

            <div class="overflow-x-auto">
                <table class="table-input w-full">
                    <thead class="thead-input">
                        <tr>
                            <th scope="col" class="th-input">{{ __('forms.personal_data') }}</th>
                            <th scope="col" class="th-input">{{ __('forms.document') }}</th>
                            <th scope="col" class="th-input">{{ __('forms.phone') }}</th>
                            <th scope="col" class="th-input">{{ __('patients.active_until_date') }}</th>
                            <th scope="col" class="th-input">{{ __('patients.confirmation_document') }}</th>
                            <th scope="col" class="th-input text-center">{{ __('forms.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(rep, index) in legalRepresentatives" :key="rep.id">
                            <tr>
                                <td class="td-input align-top">
                                    <div class="font-bold text-gray-900 dark:text-white" x-text="rep.fullName"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="rep.gender"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <span>РНОКПП </span><span x-text="rep.taxId"></span>
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-show="rep.unzr">
                                        <span>УНЗР </span><span x-text="rep.unzr"></span>
                                    </div>
                                </td>
                                <td class="td-input align-top">
                                    <div class="text-gray-900 dark:text-white" x-text="rep.documentType"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="rep.documentNumber"></div>
                                </td>
                                <td class="td-input align-top">
                                    <div class="text-gray-900 dark:text-white" x-text="rep.phoneType"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="rep.phone"></div>
                                </td>
                                <td class="td-input align-top">
                                    <div class="text-gray-900 dark:text-white" x-text="rep.activeUntil || '-'"></div>
                                </td>
                                <td class="td-input align-top">
                                    <div class="text-gray-900 dark:text-white" x-text="rep.confirmDocType"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="rep.confirmDocNumber"></div>
                                </td>
                                <td class="td-input text-center align-top">
                                    <div class="relative"
                                         x-data="{ openDropdown: false }"
                                         @click.outside="openDropdown = false"
                                    >
                                        <button @click="openDropdown = !openDropdown"
                                                type="button"
                                                class="cursor-pointer p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                        >
                                            @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-gray-200')
                                        </button>

                                        <div x-show="openDropdown"
                                             x-transition
                                             x-cloak
                                             class="absolute right-0 z-10 w-44 bg-white rounded shadow-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
                                        >
                                            <div class="py-1">
                                                <button type="button"
                                                        class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200"
                                                        @click="openDropdown = false"
                                                >
                                                    @icon('file-edit', 'w-4 h-4')
                                                    {{ __('forms.edit') }}
                                                </button>
                                                <button type="button"
                                                        class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-red-600 dark:text-red-400"
                                                        @click="legalRepresentatives.splice(index, 1); openDropdown = false"
                                                >
                                                    @icon('delete', 'w-4 h-4')
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
        </div>

        <button type="button"
                @click="showLegalRepDrawer = true"
                class="item-add my-5"
        >
            {{ __('patients.add_legal_representative') }}
        </button>

        {{-- Original components (commented out) --}}
        {{-- @include('livewire.person.parts.search-confidant-person')
        @include('livewire.person.parts.confidant-person') --}}
    </div>

    @include('livewire.person.parts.modals.add_legal_representative')

    <div
        x-show="showLegalRepDrawer"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
        @click="resetForm()"
        class="fixed inset-0 bg-black/25 z-30"
    ></div>
</fieldset>
