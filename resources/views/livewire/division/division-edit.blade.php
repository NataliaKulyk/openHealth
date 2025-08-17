@php
    $action = 'update';

    $divisionType = dictionary()->getDictionary('DIVISION_TYPE', false)->getValue($divisionForm->division['type']);
    $status = $divisionForm->division['status'];
@endphp

@extends('livewire.division.template.division')

@section('title')
        {{ __('forms.edit_division') }}
@endsection

@section('description')
    {{ $divisionType }} "{{ $divisionForm->division["name"] }}"
@endsection

@section('additional-buttons')
    <div class="mb-[10px] flex flex-col gap-6 xl:flex-row justify-between items-center w-full">
        <button
            type="button"
            id="save_button"
            class="button-primary cursor-pointer"
            wire:click="store"
        >
            {{ __('forms.save') }}
        </button>

        <button
            type="button"
            id="save_button"
            class="button-primary cursor-pointer"
            wire:click="update"
        >
            {{ __('forms.save_and_send') }}
        </button>

    </div>
@endsection
