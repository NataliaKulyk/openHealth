<div>
    <x-section-navigation x-data="{ showFilter: false }">
        <x-slot name="title">

        </x-slot>
    </x-section-navigation>

    <x-forms.loading/>
    <x-messages/>

    <div class="flex bg-white pb-10 p-6 flex-col">
        {{-- LegalEntity Info --}}
        <fieldset class="fieldset">
            <legend class="legend">
                <h2> {{ __('forms.legal_entity_info') }}</h2>
            </legend>

            <div class="form">
                <div class="form-row-3">
                    <div class="form-group">
                        <input value="{{ $legalEntity['edr']['public_name'] ?? '' }}" type="text"
                               name="legal_entity_name" id="legal_entity_name" class="peer input" placeholder=" "
                               required/>
                        <label for="legal_entity_name" class="label">{{ __('forms.legal_entity_name') }}</label>

                        @error('form.party.firstName') <p class="text-error">{{$message}}</p> @enderror
                    </div>
                    <div class="form-group">
                        <input value="{{ $legalEntity['edr']['name'] ?? '' }}" type="text" name="legal_entity_owner"
                               id="legal_entity_owner" class="peer input" placeholder=" " required/>
                        <label for="legal_entity_name" class="label">{{ __('forms.legal_entity_owner')}}</label>

                        @error('form.party.legal_entity_name') <p class="text-error">{{$message}}</p> @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <input value="{{ $legalEntity['edr']['name'] ?? '' }}" wire:model="form.contractorBase"
                               type="text" name="contractor_base" id="contractor_base" class="peer input"
                               placeholder=" " required/>
                        <label for="contractor_base" class="label">{{ __('forms.contract.contractorBase') }}</label>
                        @error('form.party.contractor_base') <p class="text-error">{{$message}}</p> @enderror
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <input wire:model="form.contractorRmspAmount"
                               type="number" name="contractorRmspAmount" id="contractorRmspAmount" class="peer input"
                               placeholder=" "
                               required/>
                        <label for="contractorRmspAmount"
                               class="label">{{ __('forms.contract.contractorRmspAmount') }}</label>

                        @error('form.contractorRmspAmount') <p class="text-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </fieldset>

        {{-- LegalEntity Contract Terms --}}
        <fieldset class="fieldset">
            <legend class="legend">
                <h2>{{ __('forms.contract.contracts') }}</h2>
            </legend>
            <p class="text-sm text-black mb-6">{{ __('contract.specify_type_of_contract') }}</p>
            <div class="form-row">
                <div class="form-group">
                    <select wire:model="form.idForm" name="id_form" id="id_form"
                            class="peer input appearance-none bg-white dark:bg-gray-800 dark:text-gray-400" required>
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach($this->dictionaries['REIMBURSEMENT_CONTRACT_TYPE'] as $key => $type)
                            <option value="{{ $key }}">{{ $type }}</option>
                        @endforeach
                    </select>
                    <label for="id_form" class="label">{{ __('forms.contract.contractType') }}</label>

                    @error('form.id_form')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="form-row-2 items-start">
                <div class="form-group datepicker-wrapper relative w-full">
                    <input wire:model="form.startDate" type="text" name="start_date" id="start_date"
                           class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" placeholder=" "
                           required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                    <label for="start_date" class="wrapped-label">{{ __('forms.contract.startDateContract') }}</label>
                    @error('form.party.startDate') <p class="text-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group datepicker-wrapper relative w-full">
                    <input wire:model="form.endDate" type="text" name="end_date" id="end_date"
                           class="peer input pl-10 appearance-none datepicker-input dark:text-gray-400" placeholder=" "
                           required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                    <label for="end_date" class="wrapped-label">{{ __('forms.contract.endDateContract') }}</label>
                    @error('form.party.endDate') <p class="text-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        {{-- Payment Information --}}
        <fieldset class="fieldset">
            <legend class="legend">
                <h2>{{ __('forms.paymentDetails') }}</h2>
            </legend>

            <p class="text-sm text-black mb-6">{{ __('contract.nszu_payment_account') }}</p>
            <div class="form-row-2">
                <div class="form-group">
                    <input wire:model="form.contractorPaymentDetails.bankName" type="text" name="bank_name"
                           id="bank_name" class="peer input" placeholder=" " required/>
                    <label for="bank_name" class="label">{{ __('forms.bankName') }}</label>

                    @error('form.contractorPaymentDetails.bankName')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <input wire:model="form.contractorPaymentDetails.MFO" type="text" name="MFO" id="MFO"
                           class="peer input" placeholder=" " required/>
                    <label for="MFO" class="label">{{ __('forms.mfo') }}</label>

                    @error('form.contractorPaymentDetails.MFO')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <input required
                           type="text"
                           placeholder=" "
                           class="peer input"
                           wire:model="form.contractorPaymentDetails.payerAccount"
                           x-mask="UA99 9999999 999999999999999999"
                    />
                    <label class="label">{{ __('forms.payerAccount') }}</label>

                    @error('form.contractorPaymentDetails.payerAccount')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>

        {{-- Places of service provision --}}
        <fieldset class="fieldset">
            <legend class="legend">
                <h2> {{ __('forms.placesOfService') }}</h2>
            </legend>
            <p class="text-sm text-black mb-6"> {{ __('contract.place_of_medical_service_provision') }}</p>
            <div class="form-row-3">
                <div class="form-group group">
                    <select wire:model="form.contractorDivisions"
                            type="text"
                            name="divisionName"
                            id="divisionName"
                            class="input-select"
                    >
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach($divisions as $division)
                            <option value="{{ $division['id'] }}"> {{ $division['name'] }}</option>
                        @endforeach
                    </select>

                    <label for="divisionName" class="label">{{ __('contract.division_name') }}</label>
                </div>

            </div>

            <div class="form-group mt-4">
                <button
                    type="button"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition duration-150 ease-in-out"
                    wire:click.prevent="addPlaceOfService"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    {{ __('contract.add_places_of_service') }}
                </button>
            </div>
        </fieldset>

        {{-- Involvement of a person --}}
        <div class="overflow-x-auto relative">
            <fieldset class="fieldset"
                      id="section-external-contractors"
                      x-data="{
                          openModal: false,
                          modalParty: { legalEntity: '', contractNumber: '', issuedAt: '', expiresAt: '' },
                      }"
            >
                <legend class="legend">
                    <h2>{{ __('forms.involvedPersons') }}</h2>
                </legend>

                <p class="text-sm text-black mb-6"> {{ __('contract.person_involved') }}</p>

                <table class="table-input w-inherit">
                    <thead class="thead-input">
                    <tr>
                        <th scope="col" class="td-input">{{ __('contract.name_of_the_person') }}</th>
                        <th scope="col" class="td-input">{{ __('contract.contract_number') }}</th>
                        <th scope="col" class="td-input">{{ __('contract.start_of_contract') }}</th>
                        <th scope="col" class="td-input">{{ __('contract.end_of_contract') }}</th>
                        <th scope="col" class="td-input">{{ __('forms.actions') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if(isset($external_contractors) && is_array($external_contractors))
                        @foreach($external_contractors as $key => $external_contractor)
                            <tr>
                                <td class="td-input">
                                    {{ $external_contractor['legal_entity']['name'] ?? '' }}
                                </td>
                                <td class="td-input">
                                    {{ $external_contractor['contract']['number'] ?? '' }}
                                </td>
                                <td class="td-input">
                                    {{ $external_contractor['contract']['issued_at'] ?? '' }}
                                </td>
                                <td class="td-input">
                                    {{ $external_contractor['contract']['expires_at'] ?? '' }}
                                </td>
                                <td class="td-input flex flex-row gap-2">
                                    <button wire:click.prevent="editExternalContractors({{ $key }})"
                                            class="svg-hover-action">
                                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                             width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"></path>
                                        </svg>
                                    </button>

                                    <button wire:click.prevent="deleteExternalContractors({{ $key }})"
                                            class="svg-hover-action">
                                        <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                             width="24" height="24" fill="none" viewBox="0 0 24 24">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                  stroke-width="2"
                                                  d="M5 7h14m-9 3v8m-4-8v8m-4-8v8h14m-12 4h10m-10 0a1 1 0 0 1-1-1v-1a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1a1 1 0 0 1-1 1h-10Zm3-11V7a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2m-4-2h4"></path>
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                    </tbody>
                </table>

                <button type="button"
                        class="item-add my-5"
                        @click="openModal = true; modalParty = { legalEntity: '', contractNumber: '', issuedAt: '', expiresAt: '' }"
                >
                    <span>{{ __('forms.addInvolvedPerson') }}</span>
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
                                 class="modal-content h-fit w-full max-w-6xl rounded-2xl shadow-lg bg-white"
                            >
                                <h3 class="modal-header" :id="$id('modal-title')">
                                    {{ __('forms.addInvolvedPerson') }}
                                </h3>

                                <form>
                                    <div class="form-row-modal">
                                        <div>
                                            <label for="legalEntity" class="label-modal">{{__('forms.legalEntity')}}
                                                <span class="text-red-600"> *</span></label>
                                            <input x-model="modalParty.legalEntity"
                                                   type="text"
                                                   id="legalEntity"
                                                   class="input-modal"
                                                   required
                                            >
                                        </div>

                                        <div>
                                            <label for="contractNumber"
                                                   class="label-modal">{{__('forms.externalContractorNumber')}}<span
                                                    class="text-red-600"> *</span></label>
                                            <input x-model="modalParty.contractNumber"
                                                   type="text"
                                                   id="contractNumber"
                                                   class="input-modal"
                                                   required
                                            >
                                        </div>

                                        <div class="relative">
                                            <svg
                                                class="svg-input absolute left-1 !top-2/3 transform -translate-y-1/2 pointer-events-none"
                                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20"
                                                height="20" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7a2 2 0 0 1 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm5-6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                            <label for="issuedAt"
                                                   class="label-modal">{{__('forms.externalContractorIssuedAt')}}<span
                                                    class="text-red-600"> *</span></label>
                                            <input x-model="modalParty.issuedAt"
                                                   type="text"
                                                   id="issuedAt"
                                                   class="input-modal datepicker-input"
                                                   autocomplete="off"
                                                   required
                                            >
                                        </div>

                                        <div class="relative">
                                            <svg
                                                class="svg-input absolute left-1 !top-2/3 transform -translate-y-1/2 pointer-events-none"
                                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="20"
                                                height="20" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                      d="M6 5V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h3V4a1 1 0 1 1 2 0v1h1a2 2 0 0 1 2 2v2H3V7a2 2 0 0 1 2-2h1ZM3 19v-8h18v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2Zm5-6a1 1 0 1 0 0 2h8a1 1 0 1 0 0-2H8Z"
                                                      clip-rule="evenodd"/>
                                            </svg>
                                            <label for="expiresAt"
                                                   class="label-modal">{{__('forms.externalContractorExpiresAt')}}</label>
                                            <input x-model="modalParty.expiresAt"
                                                   type="text"
                                                   id="expiresAt"
                                                   class="input-modal datepicker-input"
                                                   autocomplete="off"
                                            >
                                        </div>
                                    </div>

                                    <p class="text-sm text-gray-400 mb-2">{{ __('forms.form_required_note') }}</p>

                                    <div class="mt-6 flex flex-row items-center gap-4 border-t border-gray-200 pt-6">
                                        <button type="button"
                                                @click="openModal = false"
                                                class="button-minor"
                                        >
                                            {{__('forms.cancel')}}
                                        </button>

                                        <button type="submit"
                                                @click.prevent="$wire.addExternalContractor(modalParty); openModal = false"
                                                :class="{ 'opacity-50 cursor-not-allowed': !(modalParty.legalEntity && modalParty.contractNumber && modalParty.issuedAt) }"
                                                :disabled="!(modalParty.legalEntity && modalParty.contractNumber && modalParty.issuedAt)"
                                                class="button-primary"
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

        {{-- Block 2: Legal Entity Documents --}}
        {{-- This section handles the file uploads for the contract. --}}
        <fieldset class="fieldset">
            <legend class="legend">
                <h2>
                    {{ __('contract.uploading_documents') }}
                </h2>
            </legend>
            <div>
                <p class="text-sm text-gray-900 mb-4 leading-relaxed">
                    {{ __('contract.declaration_of_conformity') }}
                </p>

                <div class="flex flex-col gap-3">
                    <label for="statute_md5" class="block text-sm font-medium text-gray-900">
                        {{ __('forms.statuteMd5') }} *
                    </label>
                    <input id="statute_md5"
                           type="file"
                           wire:model="form.statuteMd5"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="text-xs text-gray-500">
                        {{ __('contract.file_limit') }}
                    </p>
                    @error('form.statuteMd5')
                    <x-forms.error>{{ $message }}</x-forms.error>
                    @enderror
                </div>
            </div>
            <div>
                <p class="text-sm text-gray-900 mb-4 leading-relaxed">
                    {{ __('contract.scanned_copy_of_the_document') }}
                </p>

                <div class="flex flex-col gap-3">
                    <label for="additional_document_md5" class="block text-sm font-medium text-gray-900">
                        {{ __('forms.additionalDocumentMd5') }} *
                    </label>
                    <input id="additional_document_md5"
                           type="file"
                           wire:model="form.additionalDocumentMd5"
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                    >
                    <p class="text-xs text-gray-500">
                        {{ __('contract.file_limit') }}
                    </p>
                    @error('form.additionalDocumentMd5')
                    <x-forms.error>{{ $message }}</x-forms.error>
                    @enderror
                </div>
            </div>
        </fieldset>

        {{-- Agreement --}}
        <fieldset class="fieldset">
            <legend class="legend">
                <h2>{{ __('contract.text_agreement') }}</h2>
            </legend>
            <div class='flex flex-col gap-9'>
                <div class='dark:bg-boxdark'>
                    <div class='border-stroke px-6.5 py-1 dark:border-strokedark'>
                        <h3 class='font-medium text-black dark:text-white'>
                        </h3>
                    </div>

                    <div class='flex flex-col gap-5.5 p-6.5'>
                        <p class='ms-2 text-sm font-regular text-justify text-gray-900 dark:text-gray-300'>
                            {{ $dictionaries['CAPITATION_CONTRACT_CONSENT_TEXT']['APPROVED'] }}
                        </p>

                        <x-forms.form-group class='mt-4 pl-2'>
                            <x-slot name='input'>

                                <div class="flex items-center">
                                    <x-forms.checkbox
                                        wire:model="form.consentText"
                                        id="consent_text"
                                        type='checkbox'
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                                    />

                                    <label for='consent_text'
                                           class='ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer'>
                                        {{ __('forms.agree') }}
                                    </label>
                                </div>
                            </x-slot>

                            @error('form.consent_text')
                            <x-slot name='error'>
                                <x-forms.error>
                                    {{ $message }}
                                </x-forms.error>
                            </x-slot>
                            @enderror
                        </x-forms.form-group>
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset">
            <legend class="legend">
                <h2>{{ __('contract.cep') }}</h2>
            </legend>

            <p class="text-sm text-black mb-6"> {{ __('contract.author_identification') }}</p>
            <div class="flex items-center mb-6">
                <x-forms.checkbox
                    id='consent_text'
                    type='checkbox'
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-700 dark:focus:ring-offset-gray-700 focus:ring-2 dark:bg-gray-600 dark:border-gray-500"
                />

                <label for='consent_text'
                       class='ml-2 text-sm font-medium text-gray-900 dark:text-gray-300 cursor-pointer '>
                    {{ __('contract.signing_cep') }}
                </label>
            </div>
            <p class="text-sm text-black mb-6"> {{ __('contract.signing_cep_agree') }}</p>
        </fieldset>

        <div class='mb-4.5 pt-10 flex flex-col gap-6 xl:flex-row justify-between items-center'>
            <x-secondary-button>
                <div class='xl:w-1/4 text-left'>
                    <a href="{{ route('contracts.index', [legalEntity()]) }}">
                        {{ __('forms.back') }}
                    </a>
                </div>
            </x-secondary-button>

            <div>
                <button class="button-primary-outline" wire:click.prevent="create">
                    {{ __('forms.save') }}
                </button>
            </div>

            <div class='xl:w-1/4 text-right'>
                <x-button
                    type="button"
                    wire:click='openModalSigned()'
                    class='text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800'
                >
                    {{ __('forms.sendForApproval') }}
                </x-button>
            </div>
        </div>

        <div wire:loading role="status" class="absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2">
            <svg
                aria-hidden='true'
                class='w-8 h-8 text-gray-200 animate-spin dark:text-gray-600 fill-blue-600'
                viewBox='0 0 100 101'
                fill='none'
                xmlns='http://www.w3.org/2000/svg'
            >
                <path
                    d='M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z'
                    fill='currentColor'
                />
                <path
                    d='M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z'
                    fill='currentFill'
                />
            </svg>
        </div>
    </div>

    @if($showSignatureModal)
        @include('livewire.contract._parts.modals._modal_signed_content')
    @endif
</div>
