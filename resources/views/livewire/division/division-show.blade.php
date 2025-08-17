@php
    $action = 'show';
    $status='';

    $divisionType = dictionary()->getDictionary('DIVISION_TYPE', false)->getValue($divisionForm->division['type']);
@endphp

@extends('livewire.division.template.division')

@section('title')
        {{ $divisionType }} "{{ $divisionForm->division["name"] }}"
@endsection

@section('description')
    {{ $divisionForm->division['uuid'] }}
@endsection

@section('additional-buttons')
    @if($divisionForm->division["status"] !== \App\Enums\Status::INACTIVE->value)
    @can('update', \App\Models\Division::find($divisionForm->division['id']))
            <a role="button" class="default-button cursor-pointer" href="{{ route('division.edit', [legalEntity(), $divisionForm->division['id']]) }}">
                {{ __('forms.edit') }}
            </a>
        @endcan
    @endif
@endsection
