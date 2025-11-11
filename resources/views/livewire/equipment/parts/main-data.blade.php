@use('App\Enums\Equipment\{Status, Type}')

<fieldset class="fieldset form shift-content">
    <legend class="legend">
        {{ __('forms.main_information') }}
    </legend>

    <div class="space-y-4"
         x-data="{ names: $wire.entangle('form.equipmentNames') }"
         x-init="if (!Array.isArray(names) || names.length === 0) { names = [{ name: '', typeId: '' }] }"
         x-id="['equipmentName']"
    >
        <template x-for="(equipmentName, index) in names" :key="index">
            <div x-data="{ errors: @js($errors->getMessages()) }"
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-center"
            >
                <div class="form-group group">
                    <input x-model="names[index].name"
                           type="text"
                           :name="$id('equipmentName', 'name' + index)"
                           :id="$id('equipmentName', 'name' + index)"
                           placeholder=" "
                           required
                           class="peer input"
                           :class="{ 'input-error': errors[`form.equipmentNames.${index}.name`] }"
                    >
                    <label :for="$id('equipmentName', 'name' + index)" class="label">
                        {{ __('equipments.name_medical_product') }}
                    </label>
                    <template x-if="errors[`form.equipmentNames.${index}.name`]">
                        <p class="text-error" x-text="errors[`form.equipmentNames.${index}.name`]"></p>
                    </template>
                </div>

                <div class="form-group group">
                    <select x-model="names[index].typeId"
                            :name="$id('equipmentName', 'type' + index)"
                            :id="$id('equipmentName', 'type' + index)"
                            required
                            class="peer input-select"
                            :class="{ 'input-error': errors[`form.equipmentNames.${index}.typeId`] }"
                    >
                        <option value="">{{ __('forms.select') }}</option>
                        <template x-for="(typeName, key) in $wire.dictionaries.EQUIPMENT_NAME_TYPE" :key="key">
                            <option x-text="typeName" :value="key"></option>
                        </template>
                    </select>
                    <label :for="$id('equipmentName', 'type' + index)" class="label peer-focus:text-blue-600 peer-valid:text-blue-600">
                        {{ __('equipments.name_type') }}
                    </label>
                    <template x-if="errors[`form.equipmentNames.${index}.typeId`]">
                        <p class="text-error" x-text="errors[`form.equipmentNames.${index}.typeId`]"></p>
                    </template>
                </div>

                <div class="flex items-center space-x-4">
                    <template x-if="names.length > 1">
                        <button type="button" @click.prevent="names.splice(index, 1)"
                                class="text-red-600 hover:text-red-800 item-remove justify-self-start">
                            @icon('delete', 'w-5 h-5 text-red-600')
                        </button>
                    </template>
                    <template x-if="index === names.length - 1">
                        <button type="button"
                                @click.prevent="names.push({ name: '', typeId: '' })"
                                class="text-indigo-600 hover:text-indigo-800 item-add"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            {{ __('equipments.add_name') }}
                        </button>
                    </template>
                </div>
            </div>
        </template>
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
