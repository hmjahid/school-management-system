@extends('layouts.dashboard')

@section('title', __('Bankbook') . ' — ' . config('app.name'))

@section('content')
    @include('dashboard.ledger.cashbook')
@endsection