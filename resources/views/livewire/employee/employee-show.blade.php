<div>
    <x-section-navigation class="breadcrumb-form">
        <x-slot name="title">
            {{ $pageTitle }} {{ $employee->party->fullName ?? '' }}
        </x-slot>
    </x-section-navigation>

    <div class="form space-y-8">
        {{-- The fieldset is always disabled in a "show" view --}}
        <fieldset disabled class="space-y-8">
            @include('livewire.employee.parts.employee')
            @include('livewire.employee.parts.documents')
            @include('livewire.employee.parts.position')

            {{-- Doctor-specific fields --}}
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
        <div class="mt-6 flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-6">
            <a href="{{ route('employee.index', ['legalEntity' => legalEntity()->id]) }}" class="button-minor">
                &larr; {{ __('forms.back_to_list') }}
            </a>

            {{-- THE FIX: We now generate the link for the single polymorphic 'employee.edit' route --}}
            {{-- We pass the required 'id' and 'type' parameters --}}
            @can('update', $employee)
                <a href="{{ route('employee.edit', [
                        'legalEntity' => legalEntity()->id,
                        'id' => $employee->id,
                        'type' => $employee instanceof \App\Models\Employee\EmployeeRequest ? 'request' : 'employee'
                    ]) }}" class="button-secondary">
                    {{__('forms.edit')}}
                </a>
            @endcan
        </div>
    </div>
</div>
