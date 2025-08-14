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
                        @if(!empty($authMethod['phone_number']))
                            ({{ $authMethod['phone_number'] }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    @empty($authMethods)
        <div class="bg-red-100 rounded-lg mb-10">
            <div class="flex items-center gap-2 p-4">
                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" fill="#C81E1E"
                          d="M16.5 9C16.5 13.1422 13.1422 16.5 9 16.5C4.85775 16.5 1.5 13.1422 1.5 9C1.5 4.85775 4.85775 1.5 9 1.5C13.1422 1.5 16.5 4.85775 16.5 9ZM9 15C10.5913 15 12.1174 14.3679 13.2426 13.2426C14.3679 12.1174 15 10.5913 15 9C15 7.4087 14.3679 5.88258 13.2426 4.75736C12.1174 3.63214 10.5913 3 9 3C7.4087 3 5.88258 3.63214 4.75736 4.75736C3.63214 5.88258 3 7.4087 3 9C3 10.5913 3.63214 12.1174 4.75736 13.2426C5.88258 14.3679 7.4087 15 9 15Z"
                    />
                    <path fill-rule="evenodd" clip-rule="evenodd" fill="#C81E1E"
                          d="M9 10.5C8.80109 10.5 8.61032 10.421 8.46967 10.2803C8.32902 10.1397 8.25 9.94891 8.25 9.75V6C8.25 5.80109 8.32902 5.61032 8.46967 5.46967C8.61032 5.32902 8.80109 5.25 9 5.25C9.19891 5.25 9.38968 5.32902 9.53033 5.46967C9.67098 5.61032 9.75 5.80109 9.75 6V9.75C9.75 9.94891 9.67098 10.1397 9.53033 10.2803C9.38968 10.421 9.19891 10.5 9 10.5Z"
                    />
                    <path fill="#C81E1E"
                          d="M8.25 12C8.25 11.8011 8.32902 11.6103 8.46967 11.4697C8.61032 11.329 8.80109 11.25 9 11.25C9.19891 11.25 9.38968 11.329 9.53033 11.4697C9.67098 11.6103 9.75 11.8011 9.75 12C9.75 12.1989 9.67098 12.3897 9.53033 12.5303C9.38968 12.671 9.19891 12.75 9 12.75C8.80109 12.75 8.61032 12.671 8.46967 12.5303C8.32902 12.3897 8.25 12.1989 8.25 12Z"
                    />
                </svg>
                <p class="font-semibold text-red-700">{{ __('forms.patient_has_no_auth_methods') }}</p>
            </div>
        </div>

        <a href="{{ route('patient.patient-data', [legalEntity(), $patientId]) }}"
           class="button-primary"
        >
            <svg width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8.5 8H4.5Z" fill="white"/>
                <path d="M8.5 4V8M8.5 8V12M8.5 8H12.5M8.5 8H4.5" stroke="white" stroke-width="2" stroke-linecap="round"
                      stroke-linejoin="round"
                />
            </svg>
            {{ __('forms.new_auth_method') }}
        </a>
    @endif
</fieldset>
