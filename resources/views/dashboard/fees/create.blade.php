@extends('layouts.dashboard')

@section('title', __('Add fee') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Add fee') }}</h1>
        <a href="{{ route('dashboard.fees') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>
    @include('dashboard.partials.form-errors')
    <form method="post" action="{{ route('dashboard.fees.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label><input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Code') }} *</label><input name="code" value="{{ old('code') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Class') }} *</label>
                <select name="class_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected(old('class_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Section') }}</label>
                <select name="section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('All / none') }}</option>
                    @foreach ($sections as $s)
                        <option value="{{ $s->id }}" @selected(old('section_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Student (optional)') }}</label>
                <select name="student_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('Class-wide fee') }}</option>
                    @foreach ($students as $st)
                        <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->user?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Amount') }} *</label><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Fee type') }} *</label>
                <select name="fee_type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($feeTypes as $val => $label)
                        <option value="{{ $val }}" @selected(old('fee_type', \App\Models\Fee::TYPE_TUITION) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Frequency') }} *</label>
                <select name="frequency" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($frequencies as $val => $label)
                        <option value="{{ $val }}" @selected(old('frequency', \App\Models\Fee::FREQUENCY_MONTHLY) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['active', 'inactive', 'archived'] as $st)
                        <option value="{{ $st }}" @selected(old('status', 'active') === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Start date') }}</label><input type="date" name="start_date" value="{{ old('start_date') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('End date') }}</label><input type="date" name="end_date" value="{{ old('end_date') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label><textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description') }}</textarea></div>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save fee') }}</button>
    </form>
@endsection
