<legend class="legend mb-8 text-2xl font-bold">{{ __('patients.new_alias_method') }}</legend>

<div class="form-row-3">
    <div class="form-group">
        <input type="text"
               placeholder=" "
               class="peer input"
               wire:model="alias"
        />
        <label class="label">{{ __('forms.name') }}</label>
    </div>
</div>

<div class="mt-8 flex gap-3">
    <button type="button" wire:click="setAuthStep(1)" class="button-minor">
        {{ __('forms.back') }}
    </button>

    <button type="button" wire:click="updateAliasName" class="button-primary">
        {{ __('forms.confirm') }}
    </button>
</div>
