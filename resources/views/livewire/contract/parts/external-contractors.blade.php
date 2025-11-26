<div class="overflow-x-auto relative">
    <fieldset class="fieldset"
              id="section-external-contractors"
              x-data="{
                  openModal: false,
                  externalContractor: { legalEntity: '', number: '', issuedAt: '', expiresAt: '' }
              }"
    >
        <legend class="legend">
            <h2>{{ __('contracts.external_contractor') }}</h2>
        </legend>

        <p class="default-p mb-6"> {{ __('contracts.external_contractor_info') }}</p>

        <div class="form-row">
            <div class="form-group">
                <input type="checkbox"
                       wire:model="form.externalContractorFlag"
                       class="default-checkbox"
                       id="flag"
                       name="flag"
                >
                <label for="flag" class="default-p">
                    {{ __('contracts.external_contractor_flag') }}
                </label>
            </div>
        </div>

        <table class="table-input w-inherit">
            <thead class="thead-input">
            <tr>
                <th scope="col" class="td-input">{{ __('contracts.legal_entity_name') }}</th>
                <th scope="col" class="td-input">{{ __('contracts.number') }}</th>
                <th scope="col" class="td-input">{{ __('contracts.issued_at') }}</th>
                <th scope="col" class="td-input">{{ __('contracts.expires_at') }}</th>
                <th scope="col" class="td-input">{{ __('forms.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @if(isset($externalContractors) && is_array($externalContractors))
                @foreach($externalContractors as $key => $externalContractor)
                    <tr>
                        <td class="td-input">
                            {{ $externalContractor['legal_entity']['name'] ?? '' }}
                        </td>
                        <td class="td-input">
                            {{ $externalContractor['contract']['number'] ?? '' }}
                        </td>
                        <td class="td-input">
                            {{ $externalContractor['contract']['issuedAt'] ?? '' }}
                        </td>
                        <td class="td-input">
                            {{ $externalContractor['contract']['expiresAt'] ?? '' }}
                        </td>
                        <td class="td-input flex flex-row gap-2">
                            <button wire:click.prevent="editExternalContractors({{ $key }})" class="svg-hover-action">
                                <svg class="w-6 h-6" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                     width="24" height="24" fill="none" viewBox="0 0 24 24">
                                    <path stroke="currentColor" stroke-linecap="square" stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M7 19H5a1 1 0 0 1-1-1v-1a3 3 0 0 1 3-3h1m4-6a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm7.441 1.559a1.907 1.907 0 0 1 0 2.698l-6.069 6.069L10 19l.674-3.372 6.07-6.07a1.907 1.907 0 0 1 2.697 0Z"></path>
                                </svg>
                            </button>

                            <button wire:click.prevent="deleteExternalContractors({{ $key }})" class="svg-hover-action">
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
                @click="openModal = true; externalContractor = { legalEntity: '', number: '', issuedAt: '', expiresAt: '' }"
        >
            <span>{{ __('contracts.add_external_contractor') }}</span>
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
                            {{ __('contracts.new_external_contractor') }}
                        </h3>

                        <form>
                            <div class="form-row-modal">
                                <div class="form-group group">
                                    <label for="legalEntityData" class="label-modal">
                                        {{ __('contracts.legal_entity_data') }}
                                    </label>
                                    <select x-model="externalContractors.legalEntityId"
                                            type="text"
                                            name="legalEntityData"
                                            id="legalEntityData"
                                            class="input-modal"
                                    >
                                        <option value="" selected>{{ __('forms.select') }}</option>
                                        @foreach($legalEntities as $legalEntity)
                                            <option value="{{ $legalEntity['id'] }}">
                                                {{ $legalEntity['edr']['public_name'] }}
                                                - {{ $legalEntity['edr']['edrpou'] }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('form.externalContractors.legalEntityId')
                                    <p class="text-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="number" class="label-modal">
                                        {{__('contracts.external_contractor_number')}}
                                        <span class="text-red-600"> *</span>
                                    </label>
                                    <input x-model="externalContractors.contract.number"
                                           type="text"
                                           id="number"
                                           class="input-modal"
                                           required
                                    >

                                    @error('externalContractors.contract.number')
                                    <p class="text-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group datepicker-wrapper relative w-full">
                                    <label for="issuedAt" class="label-modal">
                                        {{__('contracts.start_date_label')}}<span class="text-red-600"> *</span>
                                    </label>
                                    <input x-model="externalContractor.issuedAt"
                                           type="text"
                                           id="issuedAt"
                                           class="input-modal datepicker-input"
                                           autocomplete="off"
                                           required
                                           datepicker-format="dd.mm.yyyy"
                                    >
                                </div>

                                <div class="form-group datepicker-wrapper relative w-full">
                                    <label for="expiresAt" class="label-modal">
                                        {{__('contracts.end_date_label')}}<span class="text-red-600"> *</span>
                                    </label>
                                    <input x-model="externalContractor.expiresAt"
                                           type="text"
                                           id="expiresAt"
                                           class="input-modal datepicker-input"
                                           autocomplete="off"
                                           required
                                           datepicker-format="dd.mm.yyyy"
                                    >
                                </div>

                                <div class="form-group group">
                                    <label for="divisionName" class="label-modal">
                                        {{ __('forms.division_name') }}<span class="text-red-600"> *</span>
                                    </label>
                                    <select x-model="externalContractors.divisions.id"
                                            type="text"
                                            name="divisionName"
                                            id="divisionName"
                                            class="input-modal"
                                    >
                                        <option value="" selected>{{ __('forms.select') }}</option>
                                        @foreach($divisions as $division)
                                            <option value="{{ $division['id'] }}"> {{ $division['name'] }}</option>
                                        @endforeach
                                    </select>

                                    @error('form.externalContractors.divisions.id')
                                    <p class="text-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="medicalService" class="label-modal">
                                        {{ __('forms.service') }}<span class="text-red-600"> *</span>
                                    </label>
                                    <select x-model="externalContractors.divisions.medicalService"
                                            type="text"
                                            name="medicalService"
                                            id="medicalService"
                                            class="input-modal"
                                    >
                                        <option value="" selected>{{ __('forms.select') }}</option>
                                        @foreach($this->dictionaries['MEDICAL_SERVICE'] as $key => $medicalService)
                                            <option value="{{ $key }}"> {{ $medicalService }}</option>
                                        @endforeach
                                    </select>

                                    @error('form.externalContractors.divisions.medicalService')
                                    <p class="text-error">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <p class="text-sm text-gray-400 mb-2">{{ __('forms.form_required_note') }}</p>

                            <div class="mt-6 flex flex-row items-center gap-4 border-t border-gray-200 pt-6">
                                <button type="button"
                                        @click="openModal = false"
                                        class="button-minor"
                                >
                                    {{ __('forms.cancel') }}
                                </button>

                                <button type="submit"
                                        @click.prevent="$wire.addExternalContractor(externalContractor); openModal = false"
                                        :class="{ 'opacity-50 cursor-not-allowed': !(externalContractor.legalEntity && externalContractor.number && externalContractor.issuedAt) }"
                                        :disabled="!(externalContractor.legalEntity && externalContractor.number && externalContractor.issuedAt)"
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
