<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ $pageTitle }}
            @if(isset($employee))
                : {{ $employee->fullName }}
            @endif
        </x-slot>
    </x-section-navigation>

    <section
        class="section-form"
        x-data="{
            employeeType: $wire.entangle('form.employeeType'),
            showSignatureBlock: $wire.entangle('showSignatureBlock'),

            isDoctor() {
                return {{ Js::from(config('ehealth.doctors_type')) }}.includes(this.employeeType);
            }
        }"
    >
        <form wire:submit.prevent="save" class="form">
            {{-- Main form includes --}}
            @include('livewire.employee.parts.employee')
            @include('livewire.employee.parts.documents')
            @include('livewire.employee.parts.position')

            {{-- Doctor-specific fields --}}
            <template x-if="isDoctor()">
                <div>
                    @include('livewire.employee.parts.education')
                    @include('livewire.employee.parts.specialities')
                    @include('livewire.employee.parts.science_degree')
                    @include('livewire.employee.parts.qualifications')
                </div>
            </template>

            {{-- Signature block container, shown conditionally --}}
            <div x-show="showSignatureBlock" x-transition.opacity.duration.500ms
                 class="mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700">
                <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-white">{{ __('forms.sign_with_KEP') }}</h3>
                <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">{{ __('forms.complete_the_interaction_and_sign') }}</p>

                @include('livewire.employee.parts.signature_block')
            </div>

            {{-- Flash messages for success and error --}}
            @if (session()->has('success'))
                <div class="p-4 my-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="p-4 my-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                    <span class="font-medium">Помилка!</span> {{ session('error') }}
                </div>
            @endif

            {{-- Main action buttons --}}
            <div class="form-button-group mt-6 flex justify-between items-center border-t border-gray-200 pt-6">
                <a href="{{ route('employee.index', ['legalEntity' => legalEntity()->id]) }}" class="button-minor">
                    {{__('forms.cancel')}}
                </a>

                <div class="flex items-center space-x-4">
                    <button type="submit" class="button-primary" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">{{__('forms.save')}}</span>
                        <span wire:loading wire:target="save">Збереження...</span>
                    </button>

                </div>
            </div>
        </form>
    </section>

    <x-forms.loading/>
</div>
