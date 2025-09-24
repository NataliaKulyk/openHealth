<section class="section-form">
    <x-header-navigation class="breadcrumb-form" title="{{ __('forms.medical_service') }}">
        <x-slot name="title">{{ __('forms.medical_service') }}</x-slot>
    </x-header-navigation>

    <fieldset class="fieldset">
        <legend class="legend">
            {{ __('forms.main_information') }}
        </legend>

        <div class="form-row-2">
            <div class="form-group group">
                <select wire:model="form.divisionId"
                        type="text"
                        name="divisionName"
                        id="divisionName"
                        required
                        class="input-select"
                        disabled
                >
                    <option value="{{ $this->form->divisionId }}" selected>
                        {{ $divisionName }}
                    </option>
                </select>

                <label for="divisionName" class="label">{{ __('forms.division_name') }}</label>

                @error('form.divisionId')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group group">
                <select wire:model="form.category.coding.0.code"
                        type="text"
                        name="category"
                        id="category"
                        class="input-select @error('form.category.coding.0.code') input-error @enderror"
                        required
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach($this->dictionaries['HEALTHCARE_SERVICE_CATEGORIES'] as $key => $category)
                        <option value="{{ $key }}">{{ $category }}</option>
                    @endforeach
                </select>

                <label for="category" class="label">{{ __('healthcare-services.category') }}</label>

                @error('form.category.coding.0.code')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group group">
                <select wire:model="form.specialityType"
                        type="text"
                        name="category"
                        id="category"
                        class="input-select @error('form.specialityType') input-error @enderror"
                        required
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach($this->dictionaries['SPECIALITY_TYPE'] as $key => $type)
                        <option value="{{ $key }}">{{ $type }}</option>
                    @endforeach
                </select>

                <label for="category" class="label">{{ __('healthcare-services.speciality_type') }}</label>

                @error('form.specialityType')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group group">
                <select wire:model="form.providingCondition"
                        type="text"
                        name="providingCondition"
                        id="providingCondition"
                        class="input-select @error('form.providingCondition') input-error @enderror"
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach($this->dictionaries['PROVIDING_CONDITION'] as $key => $providingCondition)
                        <option value="{{ $key }}">{{ $providingCondition }}</option>
                    @endforeach
                </select>

                <label for="providingCondition"
                       class="label">{{ __('healthcare-services.providing_condition') }}</label>

                @error('form.providingCondition')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-row-2">
            <div class="form-group group">
                <select wire:model="form.type.coding.0.code"
                        type="text"
                        name="type"
                        id="type"
                        class="input-select @error('form.type.coding.0.code') input-error @enderror"
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach($this->dictionaries['HEALTHCARE_SERVICE_PHARMACY_DRUGS_TYPES'] as $key => $pharmacyDrugsType)
                        <option value="{{ $key }}">{{ $pharmacyDrugsType }}</option>
                    @endforeach
                </select>

                <label for="type" class="label">{{ __('healthcare-services.type') }}</label>

                @error('form.type.coding.0.code')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group group">
                <select wire:model="form.licenseId"
                        type="text"
                        name="licenseId"
                        id="licenseId"
                        class="input-select @error('form.licenseId') input-error @enderror"
                        required
                >
                    <option value="" selected>{{ __('forms.select') }}</option>
                    @foreach($licenses as $key => $license)
                        <option value="{{ $license['uuid'] }}">{{ $license['type'] }}</option>
                    @endforeach
                </select>

                <label for="licenseId" class="label">{{ __('healthcare-services.license') }}</label>

                @error('form.licenseId')
                <p class="text-error">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="form-row">
            <div>
                <label for="comment" class="label-modal">{{ __('forms.comment') }}</label>

                <div>
                    <textarea wire:model="form.comment"
                              rows="4"
                              id="comment"
                              name="comment"
                              class="textarea"
                              placeholder="{{ __('patients.write_comment_here') }}"
                    ></textarea>
                </div>
            </div>
        </div>
    </fieldset>

    <div class="flex justify-start gap-4 mt-10">
        <a href="{{ url()->previous() }}" type="button" class="button-minor">
            {{ __('forms.cancel') }}
        </a>
        <button wire:click="create" type="submit" class="button-primary">
            {{ __('forms.create') }}
        </button>
    </div>

    <x-forms.loading/>
    <x-messages/>
</section>
