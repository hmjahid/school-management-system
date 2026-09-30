@extends('layouts.dashboard')

@section('title', __('Edit payment gateway') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Edit payment gateway')" :description="$gateway->name">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payment gateways'), 'url' => route('dashboard.payment-gateways.index')],
                ['label' => __('Edit')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    @include('dashboard.payment-gateways._form', [
        'action' => route('dashboard.payment-gateways.update', $gateway),
        'method' => 'put',
        'submitLabel' => __('Update gateway'),
    ])
@endsection
