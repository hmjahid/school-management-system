@extends('layouts.app')
@section('title', '500 Server Error')
@section('content')
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <h1 class="text-8xl font-black text-brand-600">500</h1>
        <h2 class="mt-4 text-2xl font-bold text-slate-900">Something Went Wrong</h2>
        <p class="mt-2 text-slate-600">An unexpected error occurred. Our technical team has been notified. Please try again later.</p>
        <div class="mt-8 flex justify-center gap-4">
            <a href="{{ url('/') }}" class="btn-primary">Back to Home</a>
            <a href="{{ route('site.contact') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Report Issue</a>
        </div>
    </div>
</div>
@endsection
