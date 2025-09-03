@php
    $action = 'update';
    $division = \App\Models\Division::find($divisionForm->division['id']);
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
    <div class="mb-[10px] flex flex-col gap-6 xl:flex-row justify-start items-center w-full">
        <button
            type="button"
            id="save_button"
            class="button-primary cursor-pointer"
            wire:click="store"
        >
            {{ __('forms.save') }}
        </button>

        @can('delete', $division)
            <button
                x-on:click.prevent="
                    divisionId={{ $division->id }};
                    textConfirmation=@js(__('divisions.modals.delete.confirmation_text'));
                    actionType='delete';
                    actionTitle=@js(__('divisions.modals.delete.title'));
                    actionButtonText=@js(__('forms.delete'));
                "
                class="alternative-button cursor-pointer mt-2"
            >
                {{ __('forms.delete') }}
            </button>
        @endcan

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
