@use('App\Enums\Equipment\{Status, Type, AvailabilityStatus}')

<section class="section-form">
    <x-header-navigation x-data="{ showFilter: false }">
        <x-slot name="title">{{ __('equipments.new') }}</x-slot>
    </x-header-navigation>

    <div class="form" wire:key="{{ random_bytes(1) }}">
        <fieldset class="fieldset form shift-content">
            <legend class="legend">
                {{ __('forms.main_information') }}
            </legend>

            <div class="form-row-2">
                <div class="form-group group">
                    <input wire:model="form.names.0.name"
                           type="text"
                           name="equipmentName"
                           id="equipmentName"
                           placeholder=" "
                           required
                           class="peer input"
                    >
                    <label for="equipmentName" class="label">
                        {{ __('equipments.name_medical_product') }}
                    </label>

                    @error('form.names.*.name')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group group">
                    <select wire:model="form.names.0.type"
                            name="typeName"
                            id="typeName"
                            required
                            class="peer input-select"
                    >
                        <option value="">{{ __('forms.select') }}</option>
                        @foreach(Type::options() as $key => $nameType)
                            <option value="{{ $key }}">{{ $nameType }}</option>
                        @endforeach
                    </select>
                    <label for="typeName" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                        {{ __('equipments.name_type') }}
                    </label>

                    @error('form.names.*.type')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group group">
                    <select wire:model="form.type"
                            name="typeMedicalDevice"
                            id="typeMedicalDevice"
                            required
                            class="peer input-select"
                    >
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach(dictionary()->getDictionary('device_definition_classification_type') as $key => $type)
                            <option value="{{ $key }}">{{ $type }}</option>
                        @endforeach
                    </select>
                    <label for="typeMedicalDevice" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                        {{ __('equipments.type_medical_device') }}
                    </label>

                    @error('form.type')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group group">
                    <input wire:model="form.serialNumber"
                           type="text"
                           name="serialNumber"
                           id="serialNumber"
                           placeholder=" "
                           class="peer input"
                    >
                    <label for="serialNumber" class="label">
                        {{ __('equipments.serial_number') }}
                    </label>

                    @error('form.serialNumber')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group group">
                    <input value="{{ Status::from($form->status)->label() }}"
                           type="text"
                           name="status"
                           id="status"
                           placeholder=" "
                           class="peer input"
                           disabled
                           readonly
                    >
                    <label for="status" class="label">
                        {{ __('forms.status.label') }}
                    </label>

                    @error('form.status')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group group">
                    <input value="{{ $recorderFullName }}"
                           type="text"
                           name="recorder"
                           id="recorder"
                           placeholder=" "
                           class="peer input"
                           disabled
                    >
                    <label for="recorder" class="label">
                        {{ __('equipments.recorder') }}
                    </label>

                    @error('form.recorder')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </fieldset>

        <fieldset class="fieldset form shift-content">
            <legend class="legend">
                {{ __('equipments.additional_data') }}
            </legend>

            <div class="form-row-2">
                <div class="form-group group">
                    <select wire:model="form.divisionId"
                            name="divisionId"
                            id="divisionId"
                            class="peer input-select"
                    >
                        <option value="" selected>{{ __('forms.select') }}</option>
                        @foreach($divisions as $key => $division)
                            <option value="{{ $division['uuid'] }}">{{ $division['name'] }}</option>
                        @endforeach
                    </select>
                    <label for="divisionId" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                        {{ __('forms.division_name') }}
                    </label>

                    @error('form.divisionId')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group group">
                    <select name="availabilityStatus"
                            id="availabilityStatus"
                            class="peer input-select"
                            required
                            disabled
                    >
                        <option value="" selected>
                            {{ AvailabilityStatus::from($form->availabilityStatus)->label() }}
                        </option>
                    </select>
                    <label for="availabilityStatus" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                        {{ __('equipments.availability_status.label') }}
                    </label>

                    @error('form.availabilityStatus')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group group">
                    <input wire:model="form.inventoryNumber"
                           type="text"
                           name="inventoryNumber"
                           id="inventoryNumber"
                           placeholder=" "
                           class="peer input"
                    >
                    <label for="inventoryNumber" class="label">
                        {{ __('equipments.inventory_number') }}
                    </label>

                    @error('form.inventoryNumber')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group group">
                    <input wire:model="form.manufacturer"
                           type="text"
                           name="manufacturer"
                           id="manufacturer"
                           placeholder=" "
                           class="peer input"
                    >
                    <label for="manufacturer" class="label">
                        {{ __('equipments.manufacturer') }}
                    </label>

                    @error('form.manufacturer')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group datepicker-wrapper relative w-full">
                    <input wire:model="form.manufactureDate"
                           type="text"
                           name="manufactureDate"
                           id="manufactureDate"
                           class="peer input pl-10 datepicker-input"
                           datepicker-max-date="{{ now() }}"
                           placeholder=" "
                    >
                    <label for="manufactureDate" class="wrapped-label">{{ __('equipments.manufacture_date') }}</label>

                    @error('form.manufactureDate') <p class="text-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group datepicker-wrapper relative w-full">
                    <input wire:model="form.expirationDate"
                           type="text"
                           name="expirationDate"
                           id="expirationDate"
                           class="peer input pl-10 datepicker-input"
                           placeholder=" "
                           required
                    >
                    <label for="expirationDate" class="wrapped-label">{{__('equipments.expiration_date')}}</label>

                    @error('form.expirationDate')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group group">
                    <input wire:model="form.modelNumber"
                           type="text"
                           name="modelNumber"
                           id="modelNumber"
                           placeholder=" "
                           class="peer input"
                    >
                    <label for="modelNumber" class="label">
                        {{ __('equipments.model_number') }}
                    </label>

                    @error('form.modelNumber')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group group">
                    <input wire:model="form.lotNumber"
                           type="text"
                           name="lotNumber"
                           id="lotNumber"
                           placeholder=" "
                           class="peer input"
                    >
                    <label for="lotNumber" class="label">
                        {{ __('equipments.lot_number') }}
                    </label>

                    @error('form.lotNumber')
                    <p class="text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label for="comment" class="label-modal">{{ __('forms.comment') }}</label>
                    <textarea wire:model="form.comment"
                              rows="4"
                              id="comment"
                              name="comment"
                              class="textarea"
                              placeholder="{{ __('forms.write_comment_here') }}"
                    ></textarea>

                    @error('form.notesComments') <p class="text-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </fieldset>

        <div class="mt-6 flex flex-row items-center gap-4 pt-6">
            <div class="flex items-center space-x-3">
                <a href="" class="button-minor">
                    {{__('forms.cancel')}}
                </a>
                <button type="submit"
                        class="button-primary-outline flex items-center gap-2 px-4 py-2"
                        wire:loading.attr="disabled"
                        wire:target="save"
                >
                    <svg
                        class="w-5 h-5"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path stroke-linejoin="round"
                              d="M10 12v1h4v-1m4 7H6a1 1 0 0 1-1-1V9h14v9a1 1 0 0 1-1 1ZM4 5h16a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z"/>
                    </svg>
                    <span wire:loading.remove wire:target="save">{{ __('forms.save') }}</span>
                    <span wire:loading wire:target="save">{{ __('forms.saving') }}...</span>
                </button>
                <button type="button" wire:click="create" class="button-primary">
                    {{ __('forms.create') }}
                </button>
            </div>
        </div>
    </div>

    <x-messages/>
    <x-forms.loading/>
</section>
