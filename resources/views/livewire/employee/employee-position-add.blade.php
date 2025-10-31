<div>
    <x-header-navigation class="breadcrumb-form shift-content">
        <x-slot name="title">{{ $pageTitle ?? '' }}</x-slot>
    </x-header-navigation>

    <section class="section-form shift-content" x-data="{ ... }">
        <form wire:submit.prevent="save" class="form space-y-8">

            {{-- 1: position (active, бо isPositionDataLocked=false) --}}
            @include('livewire.employee.parts.position') {{-- --}}

            {{--  2: doctor/specialist data (active) --}}
            <template x-if="isDoctor()">
                <div class="space-y-8">
                    @include('livewire.employee.parts.education')
                    @include('livewire.employee.parts.specialities')
                    @include('livewire.employee.parts.science_degree')
                    @include('livewire.employee.parts.qualifications')
                </div>
            </template>

            {{--  3: Party (disables, isPersonalDataLocked=true) --}}
            @include('livewire.employee.parts.party')

            {{--  4: Documents (disabled) --}}
            @include('livewire.employee.parts.documents')

            {{--  5: Buttons --}}
            @include('livewire.employee.parts.form-actions')

        </form>
    </section>

    @include('livewire.employee.parts.modals.signature-modal')
    <x-forms.loading/>

</div>
