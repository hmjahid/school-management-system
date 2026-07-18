@extends('layouts.dashboard')

@section('title', __('New announcement') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Create announcement') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Shown in student/parent portal.') }}</p>
        </div>
        <a href="{{ route('dashboard.announcements.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
    </div>

    <form method="post" action="{{ route('dashboard.announcements.store') }}" class="space-y-6">
        @csrf
        @include('dashboard.partials.form-errors')
        @include('dashboard.announcements._form', ['announcement' => $announcement])
        <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
    </form>
@endsection

