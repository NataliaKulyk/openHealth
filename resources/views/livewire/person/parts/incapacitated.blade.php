<fieldset class="fieldset"
          x-data="{
              isIncapacitated: $wire.entangle('isIncapacitated'),
              showLegalRepDrawer: false,
              showDocumentDrawer: false,
              showAuthDrawer: false,
              showSignatureDrawer: false,
              selectedPatient: null,
              documentsRelationship: $wire.entangle('form.person.confidantPerson.documentsRelationship'),
              documentTypes: @js($this->dictionaries['DOCUMENT_RELATIONSHIP_TYPE']),
              newDocument: {
                  type: '',
                  typeLabel: '',
                  number: '',
                  issuedBy: '',
                  issuedAt: '',
                  expiryDate: ''
              },
              verificationCode: '',
              timer: 60,
              isEditing: false,
              editingIndex: null,
              isEditingLegalRep: false,
              editingLegalRepIndex: null,
              addLegalRepresentative() {
                  if (this.newDocument.type && this.newDocument.number && this.newDocument.issuedBy && this.newDocument.issuedAt) {
                      if (this.isEditing && this.editingIndex !== null) {
                          // Update existing document
                          this.documentsRelationship[this.editingIndex] = {
                              type: this.newDocument.type,
                              number: this.newDocument.number,
                              issuedBy: this.newDocument.issuedBy,
                              issuedAt: this.newDocument.issuedAt,
                              activeTo: this.newDocument.expiryDate
                          };
                      } else {
                          // Add new document to the documentsRelationship array
                          this.documentsRelationship.push({
                              type: this.newDocument.type,
                              number: this.newDocument.number,
                              issuedBy: this.newDocument.issuedBy,
                              issuedAt: this.newDocument.issuedAt,
                              activeTo: this.newDocument.expiryDate
                          });
                      }

                      this.resetForm();
                  }
              },
              editDocument(index) {
                  const doc = this.documentsRelationship[index];
                  this.newDocument.type = doc.type;
                  this.newDocument.typeLabel = doc.type;
                  this.newDocument.number = doc.number;
                  this.newDocument.issuedBy = doc.issuedBy;
                  this.newDocument.issuedAt = doc.issuedAt;
                  this.newDocument.expiryDate = doc.activeTo || '';
                  this.isEditing = true;
                  this.editingIndex = index;
                  this.showDocumentDrawer = true;
              },
              editLegalRepresentative(index) {
                  // Set editing state for legal representative
                  this.isEditingLegalRep = true;
                  this.editingLegalRepIndex = index;

                  // Open the legal rep drawer with existing data
                  this.showLegalRepDrawer = true;
              },
              saveConfidantPerson() {
                  if (this.isEditingLegalRep && this.editingLegalRepIndex !== null && this.selectedPatient) {
                      this.showLegalRepDrawer = false;
                      this.isEditingLegalRep = false;
                      this.editingLegalRepIndex = null;
                  }
              },
              resetForm() {
                  this.selectedPatient = null;
                  // Reset the newDocument object
                  this.newDocument.type = '';
                  this.newDocument.typeLabel = '';
                  this.newDocument.number = '';
                  this.newDocument.issuedBy = '';
                  this.newDocument.issuedAt = '';
                  this.newDocument.expiryDate = '';
                  // Reset editing state
                  this.isEditing = false;
                  this.editingIndex = null;
                  this.showDocumentDrawer = false;
              }
          }"
>
    <legend class="legend flex items-baseline gap-2">
        <input type="checkbox"
               class="default-checkbox mb-2"
               x-model="isIncapacitated"
               id="isIncapacitated"
        />
        <label for="isIncapacitated" class="cursor-pointer">
            {{ __('patients.incapacitated') }}
        </label>
    </legend>

    <div x-show="isIncapacitated" x-cloak x-transition>
        {{-- Legal Representatives Section --}}
        <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ __('patients.legal_representatives') }}
                </h3>
                <button type="button" class="button-sync">
                    @icon('refresh', 'w-4 h-4 mr-2')
                    {{ __('patients.sync_legal_representatives') }}
                </button>
            </div>

            <div class="overflow-x-auto" x-show="documentsRelationship.length > 0">
                <table class="table-input w-full">
                    <thead class="thead-input">
                    <tr>
                        <th scope="col" class="th-input">{{ __('forms.personal_data') }}</th>
                        <th scope="col" class="th-input">{{ __('forms.document') }}</th>
                        <th scope="col" class="th-input">{{ __('forms.phone') }}</th>
                        <th scope="col" class="th-input">{{ __('patients.relationship_active_until') }}</th>
                        <th scope="col" class="th-input">{{ __('patients.relationship_confirmation_document') }}</th>
                        <th scope="col" class="th-input text-center">{{ __('forms.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($selectedConfidantPersonId)
                        @php
                            $selectedConfidant = collect($confidantPerson)->firstWhere('id', $selectedConfidantPersonId);
                        @endphp
                        @if($selectedConfidant)
                            <tr>
                                <td class="td-input align-top">
                                    <div class="font-bold text-gray-900 dark:text-white">
                                        {{ $selectedConfidant['lastName'] ?? '' }} {{ $selectedConfidant['firstName'] ?? '' }} {{ $selectedConfidant['secondName'] ?? '' }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $selectedConfidant['gender'] === 'male' ? __('patients.male') : __('patients.female') }}
                                    </div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ __('forms.rnokpp') }} </span><span>{{ $selectedConfidant['taxId'] ?? '-' }}</span>
                                    </div>
                                    @if($selectedConfidant['unzr'] ?? false)
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <span>{{ __('patients.unzr') }} </span><span>{{ $selectedConfidant['unzr'] }}</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="td-input align-top">
                                    <div class="space-y-2">
                                        <template x-for="(docRel, docIndex) in documentsRelationship"
                                                  :key="'doc-' + docIndex"
                                        >
                                            <div
                                                class="border-b border-gray-200 dark:border-gray-600 pb-2 last:border-b-0 last:pb-0">
                                                <div class="text-gray-900 dark:text-white font-medium"
                                                     x-text="documentTypes[docRel.type] || docRel.type"
                                                ></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400"
                                                     x-text="docRel.number"
                                                ></div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="td-input align-top">
                                    @empty($selectedConfidant['phones'])
                                        <div class="text-gray-900 dark:text-white">-</div>
                                    @else
                                        @foreach($selectedConfidant['phones'] as $phone)
                                            <div class="text-gray-900 dark:text-white">
                                                {{ $this->dictionaries['PHONE_TYPE'][$phone['type']] ?? '-' }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ $phone['number'] ?? '-' }}
                                            </div>
                                        @endforeach
                                    @endempty
                                </td>
                                <td class="td-input align-top">
                                    <div class="space-y-2">
                                        <template x-for="(doc, index) in documentsRelationship"
                                                  :key="'active-' + index"
                                        >
                                            <div class="border-b border-gray-200 dark:border-gray-600 pb-2 last:border-b-0 last:pb-0">
                                                <div class="text-gray-900 dark:text-white"
                                                     x-text="doc.activeTo || '-'"
                                                ></div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="td-input align-top">
                                    <div class="space-y-2">
                                        <template x-for="(doc, index) in documentsRelationship"
                                                  :key="'confirm-' + index"
                                        >
                                            <div
                                                class="border-b border-gray-200 dark:border-gray-600 pb-2 last:border-b-0 last:pb-0">
                                                <div class="text-gray-900 dark:text-white"
                                                     x-text="documentTypes[doc.type] || doc.type"
                                                ></div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400"
                                                     x-text="doc.number"
                                                ></div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                                <td class="td-input text-center align-top">
                                    <div class="space-y-2">
                                        <template x-for="(doc, index) in documentsRelationship"
                                                  :key="'action-' + index">
                                            <div
                                                class="border-b border-gray-200 dark:border-gray-600 pb-2 last:border-b-0 last:pb-0">
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
                                                                    @click="editLegalRepresentative(index); openDropdown = false"
                                                            >
                                                                @icon('file-edit', 'w-4 h-4')
                                                                {{ __('forms.edit') }}
                                                            </button>
                                                            <button type="button"
                                                                    class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-red-600 dark:text-red-400"
                                                                    @click="documentsRelationship.splice(index, 1); openDropdown = false"
                                                            >
                                                                @icon('delete', 'w-4 h-4')
                                                                {{ __('forms.delete') }}
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endif
                    </tbody>
                </table>
            </div>

            @unless(($this instanceof \App\Livewire\Person\PersonCreate || $this instanceof \App\Livewire\Person\PersonRequestEdit) && !empty($selectedConfidantPersonId))
                <button type="button" @click="showLegalRepDrawer = true" class="item-add my-5">
                    {{ __('patients.add_legal_representative') }}
                </button>
            @endunless

            <div class="flex justify-end mt-4">
                <button type="button" class="button-minor flex items-center gap-2">
                    @icon('delete', 'w-4 h-4')
                    {{ __('patients.deactivate_relationship') }}
                </button>
            </div>
        </div>

        @if($this instanceof \App\Livewire\Person\PersonUpdate)
            {{-- Requests Section --}}
            <div class="mt-8">
                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ __('patients.relationship_requests') }}
                    </h3>
                    <button type="button" class="button-sync">
                        @icon('refresh', 'w-4 h-4 mr-2')
                        {{ __('patients.sync_requests') }}
                    </button>
                </div>

                <table class="table-input w-full">
                    <thead class="thead-input">
                    <tr>
                        <th scope="col" class="th-input">ID</th>
                        <th scope="col" class="th-input">{{ __('forms.status.label') }}</th>
                        <th scope="col" class="th-input">{{ __('forms.action') }}</th>
                        <th scope="col" class="th-input">{{ __('patients.channel') }}</th>
                        <th scope="col" class="th-input text-center">{{ __('forms.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($relationshipRequests ?? [] as $index => $request)
                        <tr>
                            <td class="td-input text-sm text-gray-600 dark:text-gray-400">{{ $request['id'] ?? '-' }}</td>
                            <td class="td-input">
                                <span class="text-gray-700 dark:text-gray-300">{{ $request['status'] ?? '-' }}</span>
                            </td>
                            <td class="td-input text-gray-700 dark:text-gray-300">
                                {{ ($request['action'] ?? '') === 'activate' ? __('patients.activate_relationship') : __('patients.deactivate_relationship') }}
                            </td>
                            <td class="td-input text-gray-700 dark:text-gray-300">{{ $request['channel'] ?? '-' }}</td>
                            <td class="td-input text-center">
                                <div class="relative"
                                     x-data="{ openRequestDropdown: false }"
                                     @click.outside="openRequestDropdown = false"
                                >
                                    <button @click="openRequestDropdown = !openRequestDropdown"
                                            type="button"
                                            class="cursor-pointer p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                    >
                                        @icon('edit-user-outline', 'w-6 h-6 text-gray-800 dark:text-gray-200')
                                    </button>

                                    <div x-show="openRequestDropdown"
                                         x-transition
                                         x-cloak
                                         class="absolute right-0 z-10 w-44 bg-white rounded shadow-lg border border-gray-200 dark:bg-gray-700 dark:border-gray-600"
                                    >
                                        <div class="py-1">
                                            <button type="button"
                                                    class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200"
                                                    @click="openRequestDropdown = false"
                                            >
                                                {{ __('patients.confirm_action') }}
                                            </button>
                                            <button type="button"
                                                    class="flex items-center gap-2 w-full px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-gray-600 text-red-600 dark:text-red-400"
                                                    @click="openRequestDropdown = false"
                                            >
                                                {{ __('patients.cancel_request') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @include('livewire.person.parts.drawers.add-confidant-person')
    </div>

    <div x-show="showLegalRepDrawer"
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
