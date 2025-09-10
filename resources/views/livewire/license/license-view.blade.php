@extends('livewire.license.license')

@section('title', __('forms.license.view'))

@section('header')
    <x-section-navigation 
        :breadcrumbs="[
            ['label' => __('Головна'), 'url' => route('dashboard')],
            ['label' => __('Ліцензії'), 'url' => '/licenses'],
            ['label' => __('View')]
        ]"
        :title="__('forms.license.view')"
    />
@endsection

@section('body')
    <x-license.form :license="$license" :readonly="true" />
@endsection