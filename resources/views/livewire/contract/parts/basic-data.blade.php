@php
    use App\Livewire\Contract\CapitationContractCreate;

    $dictionary = $this instanceof CapitationContractCreate ? $this->dictionaries['CONTRACT_TYPE'] : $this->dictionaries['REIMBURSEMENT_CONTRACT_TYPE'];
@endphp

<fieldset class="fieldset">
    <legend class="legend">
        <h2>{{ __('contracts.label') }}</h2>
    </legend>

    <p class="default-p mb-6">{{ __('contracts.contract_info') }}</p>

    <div class="form-row-2">
        <div class="form-group">
            <select wire:model="form.idForm"
                    name="idForm"
                    id="idForm"
                    class="peer input-select"
                    required
            >
                <option value="" selected>{{ __('forms.select') }}</option>
                @foreach($dictionary as $key => $type)
                    <option value="{{ $key }}">{{ $type }}</option>
                @endforeach
            </select>
            <label for="idForm" class="label">{{ __('forms.type') }}</label>

            @error('form.idForm') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="form-row-2">
        <div class="form-group datepicker-wrapper relative w-full">
            <input wire:model="form.startDate"
                   type="text"
                   name="startDate"
                   id="startDate"
                   class="peer input pl-10 datepicker-input"
                   placeholder=" "
                   required
                   datepicker-format="dd.mm.yyyy"
            />
            <label for="startDate" class="wrapped-label">{{ __('contracts.start_date_label') }}</label>

            @error('form.startDate') <p class="text-error">{{ $message }}</p> @enderror
        </div>

        <div class="form-group datepicker-wrapper relative w-full">
            <input wire:model="form.endDate"
                   type="text"
                   name="endDate"
                   id="endDate"
                   class="peer input pl-10 datepicker-input"
                   placeholder=" "
                   datepicker-format="dd.mm.yyyy"
            />
            <label for="endDate" class="wrapped-label">{{ __('contracts.end_date_label') }}</label>

            @error('form.endDate') <p class="text-error">{{ $message }}</p> @enderror
        </div>
    </div>
</fieldset>
