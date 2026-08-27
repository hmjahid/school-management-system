@extends('layouts.dashboard')

@section('title', __('Cashbook') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="$title" :description="$account?->name_en ?? __('Cash account not configured')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Ledger'), 'url' => route('dashboard.ledger.index')],
                ['label' => $title],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    @include('dashboard.ledger._ledger-table')
@endsection
