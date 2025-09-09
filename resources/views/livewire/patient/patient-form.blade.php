@php
    use App\Models\Person\PersonRequest;
    $svgSprite = file_get_contents(resource_path('images/sprite.svg'));
@endphp

<div>
    <div aria-hidden="true" class="hidden">
        {!! $svgSprite !!}
    </div>

    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('patients.add_patient') }}</x-slot>
    </x-section-navigation>

    @if($viewState === 'default')
        <section class="section-form">
            <form class="form">
                @include('livewire.patient.parts.patient')
                @include('livewire.patient.parts.documents')
                @include('livewire.patient.parts.identity')
                @include('livewire.patient.parts.contact-data')
                @include('livewire.patient.parts.addresses')
                @include('livewire.patient.parts.emergency-contact')
                @include('livewire.patient.parts.incapacitated')
                @include('livewire.patient.parts.authentication-methods')

                <div class="flex xl:flex-row gap-6 justify-between items-center">
                    <a href="{{ route('patient.index', [legalEntity()]) }}" class="button-minor">
                        {{ __('forms.back') }}
                    </a>

                    @can('create', PersonRequest::class)
                        <button wire:click.prevent="createPerson" class="button-primary">
                            {{ __('forms.send_for_approval') }}
                        </button>
                        <button wire:click.prevent="createApplication" class="button-primary">
                            {{ __('patients.save_to_application') }}
                        </button>
                    @endcan
                </div>
            </form>
        </section>

    @elseif($viewState === 'new')
        <section class="section-form">
            <form class="form">
                @include('livewire.patient.parts.signature')
            </form>
        </section>
    @endif

    <x-messages/>
    <x-forms.loading/>
</div>
