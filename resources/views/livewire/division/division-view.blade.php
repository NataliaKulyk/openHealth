@php
    $action = 'show';
    $status='';
    $division = \App\Models\Division::find($divisionForm->division['id']);
    $divisionType = dictionary()->getDictionary('DIVISION_TYPE', false)->getValue($divisionForm->division['type']);
    $uuid = $divisionForm->division['uuid'];
@endphp

@extends('livewire.division.template.division')

@section('title')
        {{ $divisionType }} "{{ $divisionForm->division["name"] }}"
@endsection

@section('additional-buttons')
    @can('update', $division)
        <a role="button" class="default-button cursor-pointer" href="{{ route('division.edit', [legalEntity(), $divisionForm->division['id']]) }}">
            {{ __('forms.edit') }}
        </a>
    @endcan

    @can('activate', $division)
        <button
            x-on:click.prevent="
                divisionId={{ $division->id }};
                textConfirmation=@js(__('divisions.modals.activate.confirmation_text'));
                actionType='activate';
                actionTitle=@js(__('divisions.modals.activate.title'));
                actionButtonText=@js(__('forms.activate'));
            "
            class="alternative-button cursor-pointer mb-[8px]"
        >
            {{ __('forms.activate') }}
        </button>
    @endcan

    @can('deactivate', $division)
        <button
            x-on:click.prevent="
                divisionId={{ $division->id }};
                textConfirmation=@js(__('divisions.modals.deactivate.confirmation_text'));
                actionType='deactivate';
                actionTitle=@js(__('divisions.modals.deactivate.title'));
                actionButtonText=@js(__('forms.deactivate'));
            "
            class="alternative-button cursor-pointer mb-[8px]"
        >
            {{ __('forms.deactivate') }}
        </button>
    @endcan

    @can('delete', $division)
        <button
            x-on:click.prevent="
                divisionId={{ $division->id }};
                textConfirmation=@js(__('divisions.modals.delete.confirmation_text'));
                actionType='delete';
                actionTitle=@js(__('divisions.modals.delete.title'));
                actionButtonText=@js(__('forms.delete'));
            "
            class="alternative-button cursor-pointer mb-[8px]"
        >
            {{ __('forms.delete') }}
        </button>
    @endcan
@endsection
