@extends('layouts.dashboard')
@section('title', __('New certificate') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('New certificate') }}</h1>
    <a href="{{ route('dashboard.certificates.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.certificates.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Student') }} *</label>
            <select name="student_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">{{ __('Select student') }}</option>
                @foreach($students as $s)
                    <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ $s->user?->name }} ({{ $s->admission_number }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Certificate type') }} *</label>
            <select name="certificate_type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">{{ __('Select type') }}</option>
                @foreach($types as $t)
                    <option value="{{ $t }}" @selected(old('certificate_type') === $t)>{{ __(ucfirst($t)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Issue date') }} *</label>
            <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
            <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="draft" @selected(old('status') === 'draft')>{{ __('Draft') }}</option>
                <option value="issued" @selected(old('status') === 'issued')>{{ __('Issued') }}</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700">{{ __('Certificate body / content') }}</label>
            <textarea name="body" rows="4" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="{{ __('Enter certificate content...') }}">{{ old('body') }}</textarea>
        </div>
    </div>
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
</form>
@endsection