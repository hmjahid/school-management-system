@extends('layouts.dashboard')
@section('title', __('New Committee Member') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6">
    <a href="{{ route('dashboard.committee.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">&larr; {{ __('Back') }}</a>
    <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ __('New Committee Member') }}</h1>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.committee.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm" enctype="multipart/form-data">
    @csrf

    <div class="grid gap-6 md:grid-cols-2">
        <div>
            <label for="name" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name') }} *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label for="name_bn" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Name (Bengali)') }}</label>
            <input type="text" name="name_bn" id="name_bn" value="{{ old('name_bn') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label for="designation" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Designation') }} *</label>
            <input type="text" name="designation" id="designation" value="{{ old('designation') }}" required class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label for="designation_bn" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Designation (Bengali)') }}</label>
            <input type="text" name="designation_bn" id="designation_bn" value="{{ old('designation_bn') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label for="phone" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label for="email" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label for="photo" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Photo') }}</label>
            <input type="file" name="photo" id="photo" accept="image/*" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
        <div>
            <label for="sort_order" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Sort order') }}</label>
            <input type="number" name="sort_order" id="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        </div>
    </div>

    <div>
        <label for="bio" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Bio') }}</label>
        <textarea name="bio" id="bio" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('bio') }}</textarea>
    </div>

    <div>
        <label for="bio_bn" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Bio (Bengali)') }}</label>
        <textarea name="bio_bn" id="bio_bn" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('bio_bn') }}</textarea>
    </div>

    <label class="inline-flex cursor-pointer items-center gap-2 text-sm text-gray-700">
        <input type="checkbox" name="is_active" value="1" class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ old('is_active', true) ? 'checked' : '' }}>
        {{ __('Active') }}
    </label>

    <div class="flex items-center gap-3">
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">{{ __('Save member') }}</button>
        <a href="{{ route('dashboard.committee.index') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
    </div>
</form>
@endsection
