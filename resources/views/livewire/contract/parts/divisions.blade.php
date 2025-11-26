<fieldset class="fieldset">
    <legend class="legend">
        <h2> {{ __('forms.divisions') }}</h2>
    </legend>

    <p class="default-p mb-6"> {{ __('contracts.divisions_info') }}</p>

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
            <label for="divisionName" class="label">{{ __('forms.division_name') }}</label>

            @error('form.contractorDivisions')
                <p class="text-error">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="form-group mt-4">
        <button type="button" class="item-add">
            {{ __('forms.add_new_division') }}
        </button>
    </div>
</fieldset>
