<div>
    <x-header-navigation class="breadcrumb-form shift-content">
        <x-slot name="title">{{ $pageTitle ?? '' }}</x-slot>
    </x-header-navigation>

    <section class="section-form shift-content">
        <form wire:submit.prevent="save" class="form space-y-8">

            {{--  1: Party (Partially disabled) --}}
            @include('livewire.employee.parts.party') {{-- --}}

            {{--  2: Documents (active) --}}
            @include('livewire.employee.parts.documents') {{-- --}}

            {{--  3: Positions  --}}
            <fieldset class="fieldset" disabled>
                <legend class="legend"><h2>{{ __('forms.positions') }}</h2></legend>
            <table class="table-input w-inherit">
                <thead class="thead-input">
                <tr>
                    <th class="th-input">{{ __('forms.position') }}</th>
                    <th class="th-input">{{ __('forms.role') }}</th>
                    <th class="th-input">{{ __('forms.division') }}</th>
                    <th class="th-input">{{ __('forms.status.label') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse($partyExistingPositions ?? [] as $position)
                    <tr>
                        <td class="td-input">{{ $this->dictionaries['POSITION'][$position->position] ?? $position->position }}</td>
                        <td class="td-input">{{ $this->dictionaries['EMPLOYEE_TYPE'][$position->employee_type] ?? $position->employee_type }}</td>
                        <td class="td-input">{{ $position->division->name ?? 'N/A' }}</td>
                        <td class="td-input">
                            @if($position->status?->value === 'APPROVED')
                                <span class="badge-green">{{__('forms.status.active')}}</span>
                            @else
                                <span class="badge-red">{{__('forms.status.dismissed')}}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="td-input text-center">{{ __('forms.no_positions_found') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
            </fieldset>

            {{--  4: Buttons --}}
            @include('livewire.employee.parts.form-actions')

        </form>
    </section>

    @include('livewire.employee.parts.modals.signature-modal')
    <x-forms.loading/>

</div>
