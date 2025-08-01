<div>
    <x-section-navigation x-data="{ showFilter: false }" class=''>
        <x-slot name='title'>
            {{ $contract_request->previous_request_id === '' ? __('forms.contract.new_contract') :  __('forms.contract.editContract', ['contract' => $contract_request->previous_request_id]) }}
        </x-slot>
        {{-- <x-slot name='description'>
            {{ $contract_request->previous_request_id === '' ? __('forms.addContract') :  __('forms.editContract', ['contract' => $contract_request->previous_request_id]) }}
        </x-slot> --}}
    </x-section-navigation>

    <div class='flex bg-white pb-10 p-6 flex-col'>
        {{-- LegalEntity Info --}}
            <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
                <legend class="legend">
                    <h2> {{ __('forms.legal_entity_info') }}</h2>
                </legend>
                <div class="form">
                    <div class="form-row-3">
                            <div class="form-group">
                                <input value="{{ $legalEntity['edr']['public_name'] ?? '' }}"  type="text" name="legal_entity_name" id="legal_entity_name" class="peer input text-gray-500" placeholder=" " required />
                                <label for="legal_entity_name" class="label">{{ __('forms.legal_entity_name') }}</label>
                                @error('form.party.firstName') <p class="text-error">{{$message}}</p> @enderror
                            </div>
                            <div class="form-group">
                                <input value="{{ $legalEntity['edr']['name'] ?? '' }}"  type="text" name="legal_entity_owner" id="legal_entity_owner" class="peer input text-gray-500" placeholder=" " required />
                                <label for="legal_entity_name" class="label">{{ __('forms.legal_entity_owner')}}</label>
                                @error('form.party.legal_entity_name') <p class="text-error">{{$message}}</p> @enderror
                            </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <input value="{{ $legalEntity['edr']['name'] ?? '' }}"  type="text" name="contractor_base" id="contractor_base" class="peer input text-gray-500" placeholder=" " required />
                            <label for="contractor_base" class="label">{{ __('forms.contract.contractorBase') }}</label>
                            @error('form.party.contractor_base') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                    </div>
                    <div class="form-row-3">
                        <div class="form-group">
                            <input {{--value="{{ $legalEntity['edr']['name'] ?? '' }}"--}}  type="number" name="numberOfPeople" id="numberOfPeople" class="peer input text-gray-500" placeholder=" " required />
                            <label for="numberOfPeople" class="label">{{ __('forms.contract.numberOfPeople') }}</label>
                            @error('form.party.numberOfPeople') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                    </div>

            </div>
            </fieldset>
        {{-- Block 2: Legal Entity Documents --}}
        {{-- This section handles the file uploads for the contract. --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2>{{ __('forms.documentsMedicalOrganization') }}</h2>
            </legend>
            <div class='grid grid-cols-1 gap-9 sm:grid-cols-2'>
                <div class='flex flex-col gap-5.5'>
                    <x-forms.form-group>
                        <x-slot name='label'>
                            <x-forms.label for='statute_md5' class='default-label'>{{ __('forms.statuteMd5') }} *</x-forms.label>
                        </x-slot>
                        <x-slot name='input'>
                            {{--
                                FIX: The 'Undefined variable $file' error is solved here.
                                By passing ':file="null"', we explicitly initialize the $file variable
                                within the component's scope, preventing the error without modifying the component file itself.
                            --}}
                            <x-forms.file :file="null" wire:model='contract_request.statute_md5' type='file' id='statute_md5' />
                        </x-slot>
                        @error('contract_request.statute_md5')
                        <x-forms.error>{{ $message }}</x-forms.error>
                        @enderror
                    </x-forms.form-group>
                </div>
                <div class='flex flex-col gap-5.5'>
                    <x-forms.form-group>
                        <x-slot name='label'>
                            <x-forms.label for='additional_document_md5' class='default-label'>{{ __('forms.additionalDocumentMd5') }} *</x-forms.label>
                        </x-slot>
                        <x-slot name='input'>
                            {{-- The same fix is applied here for the second file input. --}}
                            <x-forms.file :file="null" wire:model='contract_request.additional_document_md5' type='file' id='additional_document_md5' />
                        </x-slot>
                        @error('contract_request.additional_document_md5')
                        <x-forms.error>{{ $message }}</x-forms.error>
                        @enderror
                    </x-forms.form-group>
                </div>
            </div>
        </fieldset>

        {{-- LegalEntity Contract Terms --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2>{{ __('forms.contract.contracts') }}</h2>
            </legend>
            <div class="form-row">
            <div class="form-group">
                <select wire:model="contract_request.id_form" name="id_form" id="id_form" class="peer input appearance-none bg-white text-gray-500 dark:bg-gray-800 dark:text-gray-400" required>
                    <option value="" disabled selected hidden>{{ __('forms.select') }} {{ __('forms.contract.contractType') }}</option>
                    @foreach($this->dictionaries['CONTRACT_TYPE'] as $key => $contract_type)
                        <option value="{{ $key }}">{{ $contract_type }}</option>
                    @endforeach
                </select>
                <label for="id_form" class="label">{{ __('forms.contract.contractType') }}</label>
                @error('contract_request.id_form')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
            </div>
                        <div class="form-row-2 items-start">
                        <div class="form-group datepicker-wrapper relative w-full">
                            <input wire:model='contract_request.start_date' type="text" name="start_date" id="start_date" class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                            <label for="start_date" class="wrapped-label">{{ __('forms.contract.startDateContract') }}</label>
                            @error('form.party.start_date') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                        <div class="form-group datepicker-wrapper relative w-full">
                            <input wire:model='contract_request.end_date' type="text" name="end_date" id="end_date" class="peer input pl-10 appearance-none datepicker-input text-gray-500 dark:text-gray-400" placeholder=" " required datepicker-autohide datepicker-format="yyyy-mm-dd" datepicker-button="false"/>
                            <label for="end_date" class="wrapped-label">{{ __('forms.contract.endDateContract') }}</label>
                            @error('form.party.end_date') <p class="text-error">{{$message}}</p> @enderror
                        </div>
                        </div>
        </fieldset>

        {{-- Payment Information --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2>{{ __('forms.paymentDetails') }}</h2>
            </legend>
            <div class="form-row-3">
                <div class="form-group">
                    <input wire:model='contract_request.contractor_payment_details.bank_name'  type="text" name="bank_name" id="bank_name" class="peer input text-gray-500" placeholder=" " required />
                    <label for="bank_name" class="label">{{ __('forms.bankName') }}</label>
                    @error('form.party.bank_name') <p class="text-error">{{$message}}</p> @enderror
                </div>
                <div class="form-group">
                    <input wire:model='contract_request.contractor_payment_details.bank_name'  type="text" name="MFO" id="MFO" class="peer input text-gray-500" placeholder=" " required />
                    <label for="MFO" class="label">{{ __('forms.mfo') }}</label>
                    @error('form.party.MFO') <p class="text-error">{{$message}}</p> @enderror
                </div>
            </div>
            <div class="form-row-3">
                <div class="form-group">
                    <input
                        required
                        type="text"
                        placeholder=" "
                        class="peer input"
                        wire:model="contract_request.contractor_payment_details.payer_account"
                        x-data
                        x-mask="UA99 9999999 999999999999999999"
                    />
                    <label class="label">{{ __('forms.payerAccount') }}</label>
                    @error('contract_request.contractor_payment_details.payer_account')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>

        {{-- Places of service provision --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2> {{ __('forms.placesOfService') }}</h2>
            </legend>
            <div class="form-row-3">
                <div class="form-group">
                    <label class="label" for="contractor_divisions">{{ __('forms.contract.chooseLocation') }}</label>
                    <select id="contractor_divisions"
                            class="input-select @error('contract_request.contractor_divisions') input-error @enderror"
                            wire:model="contract_request.contractor_divisions"
                            multiple
                            size="5"
                    >
                        @if($divisions)
                            @foreach($divisions as $division)
                                <option value="{{$division->uuid}}">{{$division->name}}</option>
                            @endforeach
                        @endif
                    </select>
                    @error('contract_request.contractor_divisions')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>

        {{-- Involved Person --}}
        <fieldset class="fieldset" x-data="{ party: $wire.entangle('form.party') }">
            <legend class="legend">
                <h2>{{ __('forms.involvedPersons') }}</h2>
            </legend>
                    <div class='flex flex-col gap-5.5 p-6.5'>
                        @if($external_contractors)

                            <table class='w-full table-auto'>
                                <thead>
                                    <tr class='bg-gray-2 text-left dark:bg-meta-4'>
                                        <th class='px-4 py-4 font-medium text-black dark:text-white'>
                                            {{ __('forms.legalEntity') }}
                                        </th>

                                        <th class='min-w-[220px] px-4 py-4 font-medium text-black dark:text-white xl:pl-11'>
                                            {{ __('forms.externalContractorNumber') }}
                                        </th>

                                        <th class='min-w-[150px] px-4 py-4 font-medium text-black dark:text-white'>
                                            {{ __('forms.externalContractorIssuedAt') }}
                                        </th>

                                        <th class="px-4 py-4 font-medium text-black dark:text-white">
                                            {{ __('forms.externalContractorExpiresAt') }}
                                        </th>

                                        <th class='px-4 py-4 font-medium text-black dark:text-white'>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($external_contractors as $key => $external_contractor)
                                    <tr>
                                        <td class='border-b border-[#eee] px-4 py-5 pl-9 dark:border-strokedark xl:pl-11'>
                                            {{ $external_contractor['legal_entity']['name'] ?? '' }}
                                        </td>

                                        <td class='border-b border-[#eee] px-4 py-5 dark:border-strokedark'>
                                            {{ $external_contractor['contract']['number'] ?? '' }}
                                        </td>

                                        <td class='border-b border-[#eee] px-4 py-5 dark:border-strokedark'>
                                            {{ $external_contractor['contract']['issued_at'] ?? '' }}

                                        </td>

                                        <td class='border-b border-[#eee] px-4 py-5 dark:border-strokedark'>
                                            {{ $external_contractor['contract']['expires_at'] ?? '' }}
                                        </td>

                                        <td class='border-b border-[#eee] flex px-4 py-5 dark:border-strokedark'>
                                            <a wire:click.prevent="editExternalContractors({{$key}})" href=''>
                                                <svg
                                                    xmlns='http://www.w3.org/2000/svg'
                                                    fill='none'
                                                    viewBox='0 0 24 24'
                                                    stroke-width='1.5'
                                                    stroke='currentColor'
                                                    class='w-6 h-6'
                                                >
                                                    <path
                                                        stroke-linecap='round'
                                                        stroke-linejoin='round'
                                                        d='m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10'/>
                                                </svg>
                                            </a>
                                            <a wire:click.prevent="deleteExternalContractors({{$key}})" href=''>
                                                <svg
                                                    xmlns='http://www.w3.org/2000/svg'
                                                    fill='none'
                                                    viewBox='0 0 24 24'
                                                    stroke-width='1.5'
                                                    stroke='currentColor'
                                                    class='w-6 h-6'
                                                >
                                                    <path
                                                        stroke-linecap='round'
                                                        stroke-linejoin='round'
                                                        d='m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0'
                                                    />
                                                </svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                            <button
                                type="button"
                                class="item-add"
                                wire:click.prevent="openModal('addExternalContractors')"
                            >
                                <span>{{ __('forms.addInvolvedPerson') }}</span>
                            </button>
                    </div>
        </fieldset>
        {{-- Agreement --}}
        <div class='w-full mt-4 bg-white border-t border-gray-200 dark:border-gray-700'>
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
                                <x-forms.checkbox
                                    wire:model='contract_request.consent_text'
                                    id='consent_text'
                                    type='checkbox'
                                />
                                <label for='consent_text' class='ms-2 text-sm font-medium text-gray-900 dark:text-gray-300'>
                                    {{ __('forms.agree') }}
                                </label>
                            </x-slot>
                            @error('contract_request.consent_text')
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
        </div>

        <div class='mb-4.5 pt-10 flex flex-col gap-6 xl:flex-row justify-between items-center'>
            <x-secondary-button>
                <div class='xl:w-1/4 text-left'>
                    <a href="{{ route('contract.index', [legalEntity()]) }}">
                        {{ __('forms.back') }}
                    </a>
                </div>
            </x-secondary-button>

            <div class='xl:w-1/4 text-right'>
                <x-button
                    type='button'
                    wire:click='openModalSigned()'
                    class='text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 dark:bg-blue-600 dark:hover:bg-blue-700 focus:outline-none dark:focus:ring-blue-800'
                >
                    {{ __('forms.sendForApproval') }}
                </x-button>
            </div>
        </div>

        <div wire:loading role='status' class='absolute -translate-x-1/2 -translate-y-1/2 top-2/4 left-1/2'>
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
        @if($showModal == 'addExternalContractors')
            @include('livewire.contract._parts.modals._external_contractors')
        @endif
        @if($showModal == 'signed_content')
            @include('livewire.contract._parts.modals._modal_signed_content')
        @endif

    </div>
</div>
