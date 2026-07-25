@extends('layouts.dashboard')

@section('title', __('New notice') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Create notice') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Post a notice for staff, students, or parents.') }}</p>
        </div>
        <a href="{{ route('dashboard.notices.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
    </div>

    <form method="post" action="{{ route('dashboard.notices.store') }}" class="space-y-6">
        @csrf
        @include('dashboard.partials.form-errors')
        @include('dashboard.notices._form', ['notice' => $notice])

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
        </div>
    </form>
@endsection
