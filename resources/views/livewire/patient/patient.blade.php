@use('App\Models\Person\PersonRequest')

<div>
    <x-header-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('patients.add_patient') }}</x-slot>
    </x-header-navigation>

    @if($viewState === 'default')
        <section wire:key="{{ $viewState }}" class="section-form shift-content">
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
                        <button wire:click.prevent="createApplication" class="button-primary-outline">
                            {{ __('forms.save') }}
                        </button>
                        <button wire:click.prevent="createPerson" class="button-primary">
                            {{ __('forms.save_and_send') }}
                        </button>
                    @endcan
                </div>
            </form>
        </section>

    @elseif($viewState === 'new')
        <section class="section-form">
            <form class="form">
                @include('livewire.patient.parts.sign')
            </form>
        </section>
    @endif

    @if($showLeafletModal)
        @include('livewire.patient.parts.modals.leaflet')
    @endif

    @if($showSignatureModal)
        @include('livewire.patient.parts.modals.signature')
    @endif

    <x-messages/>
    <x-forms.loading/>
</div>
