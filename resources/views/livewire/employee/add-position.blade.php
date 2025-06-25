<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ $pageTitle }}
            {{-- We can show the name of the person we're adding a position to --}}
            : {{ $this->form->party['lastName'] }} {{ $this->form->party['firstName'] }}
        </x-slot>
    </x-section-navigation>

    <section class="section-form" x-data="{ isDoctor: @js($this->form->employeeType === 'DOCTOR'), showSignatureBlock: $wire.entangle('showSignatureBlock') }">
        <form wire:submit.prevent="save" class="form space-y-8">

            {{-- 1. Personal data is included but will be disabled by the component's logic --}}
            @include('livewire.employee.parts.employee')

            {{-- 2. Documents are included, but actions will be disabled --}}
            @include('livewire.employee.parts.documents')

            {{-- 3. Position fields are included and will be active --}}
            @include('livewire.employee.parts.position')

            {{-- 4. Doctor-specific fields --}}
            <template x-if="isDoctor">
                <div class="space-y-8">
                    @include('livewire.employee.parts.education')
                    @include('livewire.employee.parts.specialities')
                    @include('livewire.employee.parts.science_degree')
                    @include('livewire.employee.parts.qualifications')
                </div>
            </template>

            {{-- 5. Signature block and action buttons --}}
            <div x-show="showSignatureBlock" x-transition.opacity.duration.500ms class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md border">
                @include('livewire.employee.parts.signature_block')
            </div>

            @if (session()->has('success'))
                <div class="p-4 my-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            <div class="form-button-group mt-6 flex justify-between items-center border-t border-gray-200 pt-6">
                <a href="{{ route('employee.index', ['legalEntity' => legalEntity()->id]) }}" class="button-minor">{{__('forms.cancel')}}</a>
                <div class="flex items-center space-x-4">
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{__('forms.save')}}</span>
                        <span wire:loading wire:target="save">Збереження...</span>
                    </button>
                </div>
            </div>
        </form>
    </section>
</div>
