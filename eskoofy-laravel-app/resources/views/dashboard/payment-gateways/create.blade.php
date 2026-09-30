@extends('layouts.dashboard')

@section('title', __('Add payment gateway') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Add payment gateway')" :description="__('Register any gateway with its credentials and endpoints. Enable it to offer it to payers.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payment gateways'), 'url' => route('dashboard.payment-gateways.index')],
                ['label' => __('Add')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    @include('dashboard.payment-gateways._form', [
        'action' => route('dashboard.payment-gateways.store'),
        'method' => 'post',
        'submitLabel' => __('Create gateway'),
    ])
@endsection
