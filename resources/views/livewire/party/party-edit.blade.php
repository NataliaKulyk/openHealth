<div>
    <x-header-navigation class="breadcrumb-form shift-content">
        <x-slot name="title">{{ $pageTitle ?? '' }}</x-slot>
    </x-header-navigation>

    <section class="section-form shift-content">
        <form wire:submit.prevent="save" class="form space-y-8">

            {{-- БЛОК 1: Персональні дані (частково заблоковані) --}}
            @include('livewire.employee.parts.party')

            {{-- БЛОК 2: Документи (повністю активні) --}}
            @include('livewire.employee.parts.documents')

            {{-- БЛОК 3: Таблиця посад (ТЕПЕР ІНТЕРАКТИВНА) --}}
            <fieldset class="fieldset"> {{-- <-- Прибрано 'disabled' --}}
                <legend class="legend"><h2>{{ __('forms.positions') }}</h2></legend>
                <table class="table-input w-inherit">
                    <thead class="thead-input">
                    <tr>
                        <th class="th-input">{{ __('forms.position') }}</th>
                        <th class="th-input">{{ __('forms.role') }}</th>
                        <th class="th-input">{{ __('forms.division') }}</th>
                        <th class="th-input">{{ __('forms.status.label') }}</th>
                        <th class="th-input text-center">{{ __('forms.actions') }}</th> {{-- <-- Нова колонка --}}
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($partyExistingPositions ?? [] as $position)
                        <tr>
                            <td class="td-input">{{ $this->dictionaries['POSITION'][$position->position] ?? $position->position }}</td>
                            <td class="td-input">{{ $this->dictionaries['EMPLOYEE_TYPE'][$position->employee_type] ?? $position->employee_type }}</td>
                            <td class="td-input">{{ $position->division->name ?? 'N/A' }}</td>
                            <td class="td-input">
                                {{-- Нова логіка статусів --}}
                                @if($position instanceof \App\Models\Employee\Employee)
                                    @if($position->status?->value === 'APPROVED')
                                        <span class="badge-green">{{__('forms.status.active')}}</span>
                                    @else
                                        <span class="badge-red">{{__('forms.status.dismissed')}}</span>
                                    @endif
                                @elseif($position instanceof \App\Models\Employee\EmployeeRequest)
                                    <span class="badge-yellow">{{__('forms.status.draft')}}</span>
                                @endif
                            </td>
                            <td class="td-input text-center"> {{-- <-- Нова колонка --}}
                                @include('livewire.employee.parts.actions-dropdown', [
                                    'position' => $position
                                ])
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="td-input text-center">{{ __('forms.no_positions_found') }}</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </fieldset>

            {{-- БЛОК 4: Кнопки --}}
            @include('livewire.employee.parts.form-actions')

        </form>
    </section>

    @include('livewire.employee.parts.modals.signature-modal')
    <x-forms.loading/>

</div>
