<div x-data="{ showSignatureModal: $wire.entangle('showSignatureModal') }">
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

            {{-- Part 1: Personal Data --}}
            @include('livewire.employee.parts.employee')

            {{-- Part 2: Documents --}}
            @include('livewire.employee.parts.documents')

            {{-- Part 3: Position --}}
            @include('livewire.employee.parts.position')

            {{-- Part 4: Doctor-specific fields --}}
            <template x-if="isDoctor()">
                <div class="space-y-8">
                    @include('livewire.employee.parts.education')
                    @include('livewire.employee.parts.specialities')
                    @include('livewire.employee.parts.science_degree')
                    @include('livewire.employee.parts.qualifications')
                </div>
            </template>

            {{-- Action Buttons --}}
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

    @include('livewire.employee.parts.modals.signature-modal')

    <x-forms.loading/>
</div>
