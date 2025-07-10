<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              {{-- Binding documents to Alpine, it will be re-used in the modal.
                Note that it's necessary for modal to work properly --}}
              x-data="{
                  documents: $wire.entangle('form.documents'),
                  openModal: false,
                  modalDocument: new Doc(),
                  newDocument: false,
                  item: 0,
                  dictionary: @js($this->dictionaries['DOCUMENT_TYPE'])
              }"
    >
        <legend class="legend">
            <h2>{{__('forms.document')}}</h2>
        </legend>

        <table class="min-w-full table-fixed text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
            <tr>
                <th scope="col" class="px-4 py-3 whitespace-nowrap">{{ __('forms.document_type') }}</th>
                <th scope="col" class="px-4 py-3 whitespace-nowrap">{{ __('forms.number') }} </th>
                <th scope="col" class="px-4 py-3 whitespace-nowrap">{{ __('forms.issued_by') }}</th>
                <th scope="col" class="px-4 py-3 whitespace-nowrap">{{ __('forms.issued_at') }}</th>
                <th scope="col" class="px-4 py-3 whitespace-nowrap">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            <template x-for="(document, index) in documents" :key="index">
                <tr>
                    <td class="px-4 py-3 break-words max-w-[180px]" x-text="dictionary[document.type]"></td>
                    <td class="px-4 py-3" x-text="document.number"></td>
                    <td class="px-4 py-3" x-text="document.issuedBy"></td>
                    <td class="px-4 py-3" x-text="document.issuedAt"></td>
                    <td class="px-4 py-3 text-right">
                        <div x-data="{
             openDropdown: false,
             toggle() {
                 if (this.openDropdown) {
                     return this.close()
                 }
                 this.$refs.button.focus()
                 this.openDropdown = true
             },
             close(focusAfter) {
                 if (!this.openDropdown) return
                 this.openDropdown = false
                 focusAfter && focusAfter.focus()
             }
         }"
                             @keydown.escape.prevent.stop="close($refs.button)"
                             @focusin.window="! $refs.panel.contains($event.target) && close()"
                             x-id="['dropdown-button']"
                             class="relative inline-block"
                        >
                            {{-- Dropdown Button --}}
                            <button x-ref="button"
                                    @click="toggle()"
                                    :aria-expanded="openDropdown"
                                    :aria-controls="$id('dropdown-button')"
                                    type="button"
                                    class="cursor-pointer"
                            >
                                <svg class="w-6 h-6 text-gray-800 dark:text-gray-200" aria-hidden="true"
                                     xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                                     viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"/>
                                </svg>
                            </button>

                            {{-- Dropdown Panel --}}
                            <div class="absolute z-50 right-0 mt-2 w-40 bg-white rounded shadow-lg dark:bg-gray-700"
                                 x-show="openDropdown"
                                 x-transition
                                 @click.outside="close($refs.button)"
                                 :id="$id('dropdown-button')"
                                 x-cloak
                                 x-ref="panel"
                            >
                                <ul class="py-1 text-sm text-gray-700 dark:text-gray-200">
                                    <li>
                                        <button @click.prevent="
                        openModal = true;
                        item = index;
                        modalDocument = new Doc(document);
                        newDocument = false;
                        close($refs.button);
                    "
                                                class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            {{ __('forms.edit') }}
                                        </button>
                                    </li>
                                    <li>
                                        <button @click.prevent="documents.splice(index, 1); close($refs.button)"
                                                class="block w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600">
                                            {{ __('forms.delete') }}
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </td>

                </tr>
            </template>
            </tbody>
        </table>

        <div>

            {{-- Button to trigger the modal --}}
            <button @click="
                        openModal = true; {{-- Open the Modal --}}
                        newDocument = true; {{-- We are adding a new document --}}
                        modalDocument = new Doc(); {{-- Replace the data of the previous document with a new one--}}
                    "
                    @click.prevent
                    class="item-add my-5"
            >

                {{__('forms.add_document')}}
            </button>

            {{-- Modal --}}
            <template x-teleport="body"> {{-- This moves the modal at the end of the body tag --}}
                <div x-show="openModal"
                     style="display: none"
                     @keydown.escape.prevent.stop="openModal = false"
                     role="dialog"
                     aria-modal="true"
                     x-id="['modal-title']"
                     :aria-labelledby="$id('modal-title')" {{-- This associates the modal with unique ID --}}
                     class="modal"
                >

                    {{-- Overlay --}}
                    <div x-show="openModal" x-transition.opacity class="fixed inset-0 bg-black/25"></div>

                    {{-- Panel --}}
                    <div x-show="openModal"
                         x-transition
                         @click="openModal = false"
                         class="relative flex min-h-screen items-center justify-center p-4"
                    >
                        <div @click.stop
                             x-trap.noscroll.inert="openModal"
                             class="modal-content h-fit w-full max-w-2xl rounded-2xl shadow-lg bg-white"
                        >

                            {{-- Title --}}
                            <h3 class="modal-header" :id="$id('modal-title')">
                                <span x-text="newDocument ? '{{ __('forms.add_document') }}' : '{{ __('forms.edit') . ' ' . __('forms.document') }}'"></span>
                            </h3>

                            {{-- Content --}}
                            <form>
                                <div class="form-row-modal">
                                    <div>
                                        <label for="documentType"
                                               class="label-modal">{{__('forms.document_type')}}
                                        </label>
                                        <select x-model="modalDocument.type" id="documentType" class="input-modal"
                                                type="text" required>
                                            <option value="">{{__('forms.selectDocumentType')}}</option>
                                            @foreach($this->dictionaries['DOCUMENT_TYPE'] as $typeValue => $typeDescription)
                                                <option value="{{$typeValue}}">{{$typeDescription}}</option>
                                            @endforeach
                                        </select>
                                        <p class="text-error text-xs"
                                           x-show="!Object.keys(dictionary).includes(modalDocument.type) || !modalDocument.type.trim().length">{{__('forms.field_empty')}}</p>
                                    </div>

                                    <div>
                                        <label for="documentNumber"
                                               class="label-modal">{{__('forms.document_number')}}</label>
                                        <input x-model="modalDocument.number" type="text" name="documentNumber"
                                               id="documentNumber" class="input-modal" required>
                                        <p class="text-error text-xs"
                                           x-show="!modalDocument.number.trim().length > 0">{{__('forms.field_empty')}}</p>
                                    </div>

                                    <div>
                                        <label for="documentIssuedBy" class="label-modal">{{__('forms.document_issued_by')}} *</label>
                                        <input x-model="modalDocument.issuedBy" type="text" name="documentIssuedBy"
                                               id="documentIssuedBy" class="input-modal" required>
                                    </div>

                                    <div>
                                        <label for="documentIssuedAt" class="label-modal">{{__('forms.document_issued_at')}} *</label>
                                        <input x-model="modalDocument.issuedAt" name="documentIssuedAt"
                                               id="documentIssuedAt" class="input-modal datepicker-input"
                                               autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-between space-x-2">
                                    <button type="button"
                                            @click="openModal = false"
                                            class="button-minor"
                                    >
                                        {{__('forms.cancel')}}
                                    </button>

                                    <button @click.prevent
                                            @click="newDocument !== false ? documents.push(modalDocument) : documents[item] = modalDocument; openModal = false"
                                            class="button-primary"
                                            :disabled="!(modalDocument.type && modalDocument.number && modalDocument.issuedBy && modalDocument.issuedAt)"
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
    /**
     * Representation of the user's personal document
     */
    class Doc {
        type = '';
        number = '';
        issuedBy = '';
        issuedAt = '';

        constructor(obj = null) {
            if (obj) {
                Object.assign(this, obj);
            }
        }
    }
</script>
