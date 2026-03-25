@extends('layouts.dashboard')

@section('title', __('Edit parent / guardian') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit parent / guardian') }}</h1>
        <a href="{{ route('dashboard.parents.show', $guardian) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
    </div>
    @include('dashboard.partials.form-errors')
    <form method="post" action="{{ route('dashboard.parents.update', $guardian) }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label><input name="name" value="{{ old('name', $guardian->user?->name) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Email') }} *</label><input type="email" name="email" value="{{ old('email', $guardian->user?->email) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('New password') }}</label><input type="password" name="password" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Confirm password') }}</label><input type="password" name="password_confirmation" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Phone') }} *</label><input name="phone" value="{{ old('phone', $guardian->phone) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Present address') }} *</label><textarea name="present_address" rows="2" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('present_address', $guardian->present_address) }}</textarea></div>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
    </form>
@endsection
