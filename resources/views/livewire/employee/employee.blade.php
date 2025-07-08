<div
    x-data="{
        showSignatureModal: false
    }"
>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ $pageTitle }}
            @if(isset($employee))
                {{ $employee->fullName }}
            @elseif(isset($this->form->party['lastName']))
                {{ $this->form->party['lastName'] }} {{ $this->form->party['firstName'] }}
            @endif
        </x-slot>
    </x-section-navigation>

    <section
        class="section-form"
        x-data="{
            employeeType: $wire.entangle('form.employeeType'),
            isDoctor() {
                return {{ Js::from(config('ehealth.doctors_type')) }}.includes(this.employeeType);
            }
        }"
    >
        <form wire:submit.prevent="save" class="form space-y-8">
            {{-- Personal Data & Documents --}}
            @include('livewire.employee.parts.employee')
            @include('livewire.employee.parts.documents')

            {{-- Positional Data --}}
            @include('livewire.employee.parts.position')

            {{-- Doctor-specific fields --}}
            <template x-if="isDoctor()">
                <div class="space-y-8">
                    @include('livewire.employee.parts.education')
                    @include('livewire.employee.parts.specialities')
                    @include('livewire.employee.parts.science_degree')
                    @include('livewire.employee.parts.qualifications')
                </div>
            </template>

            {{-- Flash Messages --}}
            @if (session()->has('success'))
                <div class="p-4 my-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif
            {{-- Main page error is hidden if modal is open --}}
            @if (session()->has('error') && !$showSignatureModal)
                <div class="p-4 my-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                    <span class="font-medium">{{ __('forms.error') }}!</span> {{ session('error') }}
                </div>
            @endif

            <div class="form-button-group mt-6 flex justify-between items-center border-t border-gray-200 pt-6">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('employee.index', ['legalEntity' => legalEntity()->id]) }}" class="button-minor">
                        {{__('forms.cancel')}}
                    </a>
                    {{-- This button now just toggles the Alpine.js modal --}}
                    <button type="button" @click="showSignatureModal = true" class="button-sync">
                        {{ __('forms.complete_the_interaction_and_sign') }}
                    </button>
                </div>

                <div class="flex items-center space-x-4">
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{__('forms.save')}}</span>
                        <span wire:loading wire:target="save">{{__('forms.saving')}}...</span>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <template x-teleport="body">
        <div x-show="showSignatureModal" style="display: none" @keydown.escape.prevent.stop="showSignatureModal = false" role="dialog" aria-modal="true" class="modal">
            {{-- Overlay --}}
            <div x-show="showSignatureModal" x-transition.opacity class="fixed inset-0 bg-black/25"></div>

            {{-- Panel --}}
            <div x-show="showSignatureModal" x-transition @click="showSignatureModal = false" class="relative flex min-h-screen items-center justify-center p-4">
                <div @click.stop x-trap.noscroll.inert="showSignatureModal" class="modal-content h-fit w-full max-w-2xl rounded-2xl shadow-lg bg-white">

                    {{-- Title --}}
                    <h3 class="modal-header">{{ __('forms.sign_with_KEP') }}</h3>

                    {{-- Content --}}
                    <div class="p-6">
                        {{-- Error display inside the modal --}}
                        @if (session()->has('error-modal'))
                            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
                                <span class="font-medium">{{ __('forms.error') }}!</span> {{ session('error-modal') }}
                            </div>
                        @endif

                        <div class="flex flex-col gap-6">
                            {{-- KEP Provider --}}
                            <x-forms.form-group>
                                <x-slot name="label"><x-forms.label class="default-label">{{ __('forms.knedp') }} *</x-forms.label></x-slot>
                                <x-slot name="input">
                                    <x-forms.select class="default-input" wire:model="form.knedp" id="knedp">
                                        <x-slot name="option">
                                            <option value="">{{__('forms.select')}}</option>
                                            @foreach(signatureService()->getCertificateAuthorities() as $certificateType)
                                                <option value="{{ $certificateType['id'] }}" wire:key="{{ $certificateType['id'] }}">{{ $certificateType['name'] }}</option>
                                            @endforeach
                                        </x-slot>
                                    </x-forms.select>
                                </x-slot>
                                @error("form.knedp")<x-forms.error>{{ $message }}</x-forms.error>@enderror
                            </x-forms.form-group>

                            {{-- Key File --}}
                            <x-forms.form-group>
                                <x-slot name="label"><x-forms.label class="default-label">{{ __('forms.key_container_upload') }} *</x-forms.label></x-slot>
                                <x-slot name="input">
                                    <x-forms.input class="default-input" wire:model="form.keyContainerUpload" type="file" id="keyContainerUpload"/>
                                    <div wire:loading wire:target="form.keyContainerUpload" class="text-sm text-gray-500 mt-2">Uploading...</div>
                                </x-slot>
                                @error("form.keyContainerUpload")<x-forms.error>{{ $message }}</x-forms.error>@enderror
                            </x-forms.form-group>

                            {{-- Password --}}
                            <x-forms.form-group>
                                <x-slot name="label"><x-forms.label class="default-label">{{ __('forms.password') }} *</x-forms.label></x-slot>
                                <x-slot name="input"><x-forms.input class="default-input" wire:model.defer="form.password" type="password" id="password"/></x-slot>
                                @error("form.password")<x-forms.error>{{ $message }}</x-forms.error>@enderror
                            </x-forms.form-group>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" @click="showSignatureModal = false" class="button-minor">{{__('forms.cancel')}}</button>
                        <button wire:click="sign" type="button" class="button-primary" wire:loading.attr="disabled" wire:target="sign">
                            <span wire:loading.remove wire:target="sign">{{ __('forms.sign') }}</span>
                            <span wire:loading wire:target="sign">Signing...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <x-forms.loading/>
</div>
