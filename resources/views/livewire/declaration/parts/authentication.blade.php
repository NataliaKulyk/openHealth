@use('App\Enums\Person\AuthenticationMethod')

<fieldset class="fieldset">
    <legend class="legend">
        {{ __('forms.authentication') }}
    </legend>

    {{-- Patient authentication methods --}}
    <div class="form-row-2">
        <div class="form-group group">
            <label class="label" for="authorizeWith">{{ __('forms.auth_method') }}</label>
            <select wire:model="form.authorizeWith"
                    id="authorizeWith"
                    name="authorizeWith"
                    class="input-select peer"
                    type="text"
            >
                <option value="" selected>{{ __('forms.select') }}</option>
                @foreach($authMethods as $key => $authMethod)
                    <option value="{{ $authMethod['id'] }}">
                        {{ AuthenticationMethod::tryFrom($authMethod['type'])->label() }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</fieldset>
