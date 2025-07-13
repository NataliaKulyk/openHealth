<div class="bg-white dark:bg-gray-800 min-h-screen text-gray-900 dark:text-white">
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ __('forms.license.edit') }}</x-slot>
    </x-section-navigation>

    @include('livewire.license.license')
</div>
