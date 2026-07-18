@extends('layouts.app')
@section('title', '404 Not Found')
@section('content')
<div class="flex min-h-[60vh] items-center justify-center px-4">
    <div class="text-center max-w-lg">
        <h1 class="text-8xl font-black text-brand-600">404</h1>
        <h2 class="mt-4 text-2xl font-bold text-slate-900">Page Not Found</h2>
        <p class="mt-2 text-slate-600">The page you're looking for doesn't exist or has been moved. Try searching or navigate to a different page.</p>
        <div class="mt-6">
            <form action="{{ url('/news') }}" method="get" class="flex max-w-sm mx-auto gap-2">
                <input type="text" name="q" placeholder="Search..." class="admin-input flex-1">
                <button type="submit" class="btn-primary">Search</button>
            </form>
        </div>
        <div class="mt-6 flex justify-center gap-4">
            <a href="{{ url('/') }}" class="btn-primary">Back to Home</a>
            <a href="{{ route('contact') }}" class="rounded-lg border border-slate-300 bg-white px-6 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Contact Support</a>
        </div>
    </div>
</div>
@endsection
