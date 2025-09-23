@php
    $action = 'store';
    $status = '';
@endphp

@extends('livewire.division.template.division')

@section('title')
        {{ __('forms.add_new_division') }}
@endsection

@section('additional-buttons')
    <div>
    <button
            type="button"
            id="save_button"
            class="button-primary-outline"
            wire:click="store"
        >
            {{ __('forms.save') }}
        </button>

        <button
            type="button"
            id="save_button"
            class="button-primary cursor-pointer"
            wire:click="create"
        >
            {{ __('forms.save_and_send') }}
        </button>

    </div>
@endsection
