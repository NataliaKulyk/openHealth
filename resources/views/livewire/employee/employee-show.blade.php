<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">{{ $pageTitle }}: {{ $employee->fullName }}</x-slot>
    </x-section-navigation>

    <div class="p-4 sm:p-6">
        <fieldset disabled class="space-y-8">
            @include('livewire.employee.parts.employee')
            @include('livewire.employee.parts.documents')

            @if ($form->employeeType === 'DOCTOR')
                <div class="space-y-8">
                    @include('livewire.employee.parts.education')
                    @include('livewire.employee.parts.specialities')
                    @include('livewire.employee.parts.science_degree')
                    @include('livewire.employee.parts.qualifications')
                </div>
            @endif
        </fieldset>

        {{-- Action Buttons --}}
        <div class="mt-6 flex justify-between items-center border-t pt-6">
            <a href="{{ route('employee.index', ['legalEntity' => legalEntity()->id]) }}" class="button-minor">
                &larr; {{ __('forms.backToList') }}
            </a>
            <a href="{{ route('employee.edit', ['legalEntity' => legalEntity()->id, 'employeeId' => $employee->id]) }}" class="button-primary">
                {{ __('forms.goToEdit') }}
            </a>
        </div>
    </div>
</div>
