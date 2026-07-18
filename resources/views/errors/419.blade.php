@extends('layouts.app')
@section('title', 'Session Expired')
@section('content')
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <h1 class="text-8xl font-black text-brand-600">419</h1>
        <h2 class="mt-4 text-2xl font-bold text-slate-900">Session Expired</h2>
        <p class="mt-2 text-slate-600">Your session has expired due to inactivity. Please log in again to continue.</p>
        <div class="mt-8">
            <a href="{{ route('login') }}" class="btn-primary">Log In Again</a>
        </div>
    </div>
</div>
@endsection
