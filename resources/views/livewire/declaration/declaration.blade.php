@use('App\Models\DeclarationRequest')

<section class="section-form">
    <x-header-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('declarations.application_for_registration_of_declaration') }} - {{ $patientFullName }}
        </x-slot>
    </x-header-navigation>

    <form class="form">
        @include('livewire.declaration.parts.main-information')
        @include('livewire.declaration.parts.authentication')

        <div class="flex gap-8">
            <a href="{{ url()->previous() }}" type="submit" class="button-minor">
                {{ __('forms.cancel') }}
            </a>
            @can('create', DeclarationRequest::class)
                <button wire:click.prevent="createLocally" type="submit" class="button-primary-outline">
                    {{ __('declarations.create_locally') }}
                </button>
                <button wire:click.prevent="create" type="submit" class="button-primary">
                    {{ __('declarations.create_an_application') }}
                </button>
            @endcan
        </div>

        @if($showInformationMessageModal)
            @include('livewire.declaration.modals.information-message')
        @endif

        @if($showAuthModal)
            @include('livewire.declaration.modals.authentication')
        @endif

        @if($showUploadingDocumentsModal)
            @include('livewire.declaration.modals.uploading-documents')
        @endif

        @if($showSignModal)
            @include('livewire.declaration.modals.sign')
        @endif

        @if($showSignatureModal)
            @include('livewire.declaration.modals.signature')
        @endif
    </form>

    <x-messages/>
    <x-forms.loading/>
</section>
