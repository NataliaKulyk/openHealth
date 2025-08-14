<section class="section-form">
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('declarations.application_for_registration_of_declaration') }} - {{ $patientFullName }}
        </x-slot>
    </x-section-navigation>

    <form class="form">
        @include('livewire.declaration.parts.main-information')
        @include('livewire.declaration.parts.authentication')

        <div class="flex gap-8">
            <button type="submit" class="button-minor" onclick="window.history.back()">
                {{ __('forms.cancel') }}
            </button>
            <button wire:click.prevent="create" type="submit" class="button-primary">
                {{ __('declarations.create_an_application') }}
            </button>
        </div>

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

    <x-forms.loading/>
</section>
