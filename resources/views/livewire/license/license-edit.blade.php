@extends('livewire.license.license')

@section('title', __('forms.license.edit'))

@section('header')
    <x-header-navigation 
        :breadcrumbs="[
            ['label' => __('Головна'), 'url' => route('dashboard')],
            ['label' => __('Ліцензії'), 'url' => '/licenses'],
            ['label' => __('Edit')]
        ]"
        :title="__('forms.license.edit')"
    >
        <div class="button-group">
            <x-button form="license-form" class="btn-primary">{{ __('Зберегти') }}</x-button>
        </div>
    </x-header-navigation>
@endsection

@section('body')
    <x-license.form :license="$license" :readonly="false" :action="route('licenses.update', $license)" method="PUT" formId="license-form" />
@endsection