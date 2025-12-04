@use('App\Models\Person\PersonRequest')
@use('App\Livewire\Person\PersonUpdate')

<div>
    <x-header-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('patients.add_patient') }}</x-slot>
    </x-header-navigation>

    @if($viewState === 'default')
        <section wire:key="{{ $viewState }}" class="section-form shift-content">
            <form class="form" wire:key="{{ time() }}">
                @include('livewire.person.parts.person')
                @include('livewire.person.parts.documents')
                @include('livewire.person.parts.identity')
                @include('livewire.person.parts.contact-data')
                @include('livewire.person.parts.addresses')
                @include('livewire.person.parts.emergency-contact')
                @include('livewire.person.parts.incapacitated')
                @include('livewire.person.parts.authentication-methods')

                <div class="flex xl:flex-row gap-6 justify-between items-center">
                    <a href="{{ route('persons.index', [legalEntity()]) }}" class="button-minor">
                        {{ __('forms.back') }}
                    </a>

                    @if($this instanceof PersonUpdate)
                        @can('create', PersonRequest::class)
                            <button wire:click.prevent="update" class="button-primary">
                                {{ __('forms.update_data') }}
                            </button>
                        @endcan
                    @else
                        @can('create', PersonRequest::class)
                            <button wire:click.prevent="createLocally" class="button-primary-outline">
                                {{ __('forms.save') }}
                            </button>
                            <button wire:click.prevent="create" class="button-primary">
                                {{ __('forms.save_and_send') }}
                            </button>
                        @endcan
                    @endif
                </div>
            </form>
        </section>

    @elseif($viewState === 'new')
        <section class="section-form">
            <form class="form">
                @include('livewire.person.parts.sign')
            </form>
        </section>
    @endif

    @if($showLeafletModal)
        @include('livewire.person.parts.modals.leaflet')
    @endif

    @can('create', PersonRequest::class)
        <x-signature-modal method="sign" />
    @endcan

    <x-messages />
    <x-forms.loading />
</div>
