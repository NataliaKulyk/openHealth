@php
    $svgSprite = file_get_contents(resource_path('images/sprite.svg'));
@endphp

<div aria-hidden="true" class="hidden">
    {!! $svgSprite !!}
</div>

<section class="section-form">
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('patients.interaction') }} - {{ $patientFullName }}
        </x-slot>
    </x-section-navigation>

    <form class="form">
        @include('livewire.encounter.parts.aside-navigation')
        @include('livewire.encounter.parts.main-data')
        @include('livewire.encounter.parts.reasons')
        @include('livewire.encounter.parts.conditions')
        @include('livewire.encounter.parts.actions')
        @include('livewire.encounter.parts.additional-data')
        @include('livewire.encounter.parts.immunizations')
        @include('livewire.encounter.parts.diagnostic-reports')
        @include('livewire.encounter.parts.observations')
        @include('livewire.encounter.parts.procedures')
        @include('livewire.encounter.parts.clinical-impressions')

        <div class="flex gap-8">
            <button wire:click.prevent="" type="submit" class="button-minor">
                {{ __('forms.delete') }}
            </button>

            <button wire:click.prevent="save" type="submit" class="button-primary">
                {{ __('forms.save') }}
            </button>

            <button wire:click.prevent="create('signedContent')"
                    type="button"
                    class="button-sync flex items-center gap-2"
            >
                <svg width="16" height="17">
                    <use xlink:href="#svg-key"></use>
                </svg>
                {{ __('forms.complete_the_interaction_and_sign') }}
                <svg width="16" height="17">
                    <use xlink:href="#svg-arrow-right"></use>
                </svg>
            </button>
        </div>

        @if($showModal === 'signedContent')
            @include('livewire.patient.parts.modals.modal-signed-content')
        @endif
    </form>

    <x-messages/>
    <x-forms.loading/>
</section>
