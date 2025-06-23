<x-forms.form-row :cols="'flex-col'" class="">

    {{-- 1. KEP Provider Select with .defer to prevent lag --}}
    <x-forms.form-group>
        <x-slot name="label">
            <x-forms.label class="default-label" for="knedp" name="label">
                {{__('forms.knedp')}} *
            </x-forms.label>
        </x-slot>
        <x-slot name="input">
            <x-forms.select class="default-input"
                            wire:model.defer="form.knedp"
                            id="knedp">
                <x-slot name="option">
                    <option value="">{{__('forms.select')}}</option>
                    @foreach(signatureService()->getCertificateAuthorities() as $k => $certificate_type)
                        <option value="{{ $certificate_type['id'] }}">{{ $certificate_type['name'] }}</option>
                    @endforeach
                </x-slot>
            </x-forms.select>
        </x-slot>
        @error('form.knedp')
        <x-slot name="error">
            <x-forms.error>{{$message}}</x-forms.error>
        </x-slot>
        @enderror
    </x-forms.form-group>

    {{-- 2. Key File Upload with loading/success feedback --}}
    <x-forms.form-group>
        <x-slot name="label">
            <x-forms.label class="default-label" for="keyContainerUpload" name="label">
                {{__('forms.key_container_upload')}} *
            </x-forms.label>
        </x-slot>
        <x-slot name="input">
            <x-forms.file wire:model="form.keyContainerUpload"
                          :id="'keyContainerUpload'"
                          :file="$this->form->keyContainerUpload?->getClientOriginalName()"
            />

            {{-- Loading indicator during file upload --}}
            <div wire:loading wire:target="form.keyContainerUpload" class="text-sm text-gray-500 mt-2">
                Uploading file...
            </div>

            {{-- Success message appears only AFTER the file is uploaded and has no errors --}}
            @if ($this->form->keyContainerUpload && !$errors->has('form.keyContainerUpload'))
                <div class="text-sm text-green-600 mt-2">
                    ✔ File successfully uploaded: **{{ $this->form->keyContainerUpload->getClientOriginalName() }}**
                </div>
            @endif
        </x-slot>
        @error('form.keyContainerUpload')
        <x-slot name="error">
            <x-forms.error>{{$message}}</x-forms.error>
        </x-slot>
        @enderror
    </x-forms.form-group>

    {{-- 3. Password Input with .defer to prevent lag --}}
    <x-forms.form-group>
        <x-slot name="label">
            <x-forms.label class="default-label" for="password" name="label">
                {{__('forms.password')}} *
            </x-forms.label>
        </x-slot>
        <x-slot name="input">
            <x-forms.input class="default-input" wire:model.defer="form.password"
                           type="password" id="password"/>
        </x-slot>
        @error('form.password')
        <x-slot name="error">
            <x-forms.error>{{$message}}</x-forms.error>
        </x-slot>
        @enderror
    </x-forms.form-group>

</x-forms.form-row>

{{-- 4. Action Buttons with loading state on the "Sign" button --}}
<div class="form-button-group mt-6 flex justify-end">
    <button type="button" wire:click="sign" class="button-primary" wire:loading.attr="disabled" wire:target="sign">
        <span wire:loading.remove wire:target="sign">
            {{ __('forms.sign') }}
        </span>
        <span wire:loading wire:target="sign">
            Signing...
        </span>
    </button>
</div>
