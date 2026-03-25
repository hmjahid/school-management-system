@extends('layouts.dashboard')

@section('title', __('Edit fee') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit fee') }}: {{ $fee->name }}</h1>
        <a href="{{ route('dashboard.fees') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>
    @include('dashboard.partials.form-errors')
    @if ($errors->has('delete'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ $errors->first('delete') }}</div>
    @endif
    <form method="post" action="{{ route('dashboard.fees.update', $fee) }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Name') }} *</label><input name="name" value="{{ old('name', $fee->name) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Code') }} *</label><input name="code" value="{{ old('code', $fee->code) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Class') }} *</label>
                <select name="class_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($classes as $c)
                        <option value="{{ $c->id }}" @selected(old('class_id', $fee->class_id) == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Section') }}</label>
                <select name="section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($sections as $s)
                        <option value="{{ $s->id }}" @selected(old('section_id', $fee->section_id) == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Student') }}</label>
                <select name="student_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($students as $st)
                        <option value="{{ $st->id }}" @selected(old('student_id', $fee->student_id) == $st->id)>{{ $st->user?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Amount') }} *</label><input type="number" step="0.01" name="amount" value="{{ old('amount', $fee->amount) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Fee type') }} *</label>
                <select name="fee_type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($feeTypes as $val => $label)
                        <option value="{{ $val }}" @selected(old('fee_type', $fee->fee_type) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Frequency') }} *</label>
                <select name="frequency" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($frequencies as $val => $label)
                        <option value="{{ $val }}" @selected(old('frequency', $fee->frequency) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach (['active', 'inactive', 'archived'] as $st)
                        <option value="{{ $st }}" @selected(old('status', $fee->status) === $st)>{{ ucfirst($st) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Start date') }}</label><input type="date" name="start_date" value="{{ old('start_date', $fee->start_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('End date') }}</label><input type="date" name="end_date" value="{{ old('end_date', $fee->end_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label><textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description', $fee->description) }}</textarea></div>
        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save changes') }}</button>
        </div>
    </form>
    <form method="post" action="{{ route('dashboard.fees.destroy', $fee) }}" class="mt-6 border-t border-gray-200 pt-6" onsubmit="return confirm(@json(__('Delete this fee?')));">
        @csrf
        @method('DELETE')
        <button type="submit" class="rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-100">{{ __('Delete fee') }}</button>
    </form>
@endsection
