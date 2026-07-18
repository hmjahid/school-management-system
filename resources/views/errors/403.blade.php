@extends('layouts.app')
@section('title', '403 Forbidden')
@section('content')
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <h1 class="text-8xl font-black text-brand-600">403</h1>
        <h2 class="mt-4 text-2xl font-bold text-slate-900">Access Denied</h2>
        <p class="mt-2 text-slate-600">You don't have permission to access this resource. Contact your administrator if you believe this is a mistake.</p>
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ url()->previous() }}" class="btn-primary">Go Back</a>
            <a href="{{ url('/') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Back to Home</a>
        </div>
    </div>
</div>
@endsection
