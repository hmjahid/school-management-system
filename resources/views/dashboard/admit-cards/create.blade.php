@extends('layouts.dashboard')
@section('title', __('Generate admit card') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Generate admit card') }}</h1>
    <a href="{{ route('dashboard.admit-cards.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.documents._school-info-bar')
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.admit-cards.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Exam') }} *</label><select name="exam_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($exams as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Student') }} *</label><select name="student_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($students as $s)<option value="{{ $s->id }}">{{ $s->user?->name }} ({{ $s->admission_number }})</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Issue date') }} *</label><input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
    </div>

    <fieldset class="rounded-lg border border-gray-100 bg-gray-50 p-4">
        <legend class="px-2 text-sm font-semibold text-gray-900">{{ __('Document customization') }}</legend>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Header text') }}</label>
                <input type="text" name="details[header_text]" value="{{ old('details.header_text', $siteSettings?->school_name ?? config('app.name')) }}" placeholder="{{ $siteSettings?->school_name ?? config('app.name') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">{{ __('Defaults to school name if empty.') }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Footer text') }}</label>
                <input type="text" name="details[footer_text]" value="{{ old('details.footer_text', $siteSettings?->full_address ?? '') }}" placeholder="{{ $siteSettings?->full_address ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <p class="mt-1 text-xs text-gray-500">{{ __('Defaults to school address if empty.') }}</p>
            </div>
            <div>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="hidden" name="details[show_logo]" value="0">
                    <input type="checkbox" name="details[show_logo]" value="1" @checked(old('details.show_logo', true)) class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    {{ __('Show school logo') }}
                </label>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('Custom notes') }}</label>
                <textarea name="details[custom_notes]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="{{ __('Additional notes for the printed document...') }}">{{ old('details.custom_notes') }}</textarea>
            </div>
        </div>
    </fieldset>

    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Generate') }}</button>
</form>
@endsection
