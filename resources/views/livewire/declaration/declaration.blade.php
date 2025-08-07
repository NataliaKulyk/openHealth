<section class="section-form">
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ __('Заявка на реєстрацію декларації') }} - {{ $patientFullName }}
        </x-slot>
    </x-section-navigation>

    <form class="form">
        @include('livewire.declaration.parts.main-information')
        @include('livewire.declaration.parts.authentication')

        <div class="flex gap-8">
            <button type="submit" class="button-minor">
                {{ __('forms.delete') }}
            </button>
            <button wire:click.prevent="create" type="submit" class="button-primary">
                {{ __('declarations.create_an_application') }}
            </button>
        </div>

        @if($showAuthModal)
            @include('livewire.declaration.modals.authentication')
        @endif

        @if($showApproveModal)
            @include('livewire.declaration.modals.approve')
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
