@extends('layouts.dashboard')
@section('title', __('Edit admit card') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit admit card') }}</h1>
    <a href="{{ route('dashboard.admit-cards.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.admit-cards.update', $admitCard) }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf @method('put')
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Exam') }} *</label>
            <select name="exam_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($exams as $e)
                    <option value="{{ $e->id }}" @selected(old('exam_id', $admitCard->exam_id) == $e->id)>{{ $e->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Student') }} *</label>
            <select name="student_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach($students as $s)
                    <option value="{{ $s->id }}" @selected(old('student_id', $admitCard->student_id) == $s->id)>{{ $s->user?->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Issue date') }} *</label>
            <input type="date" name="issue_date" value="{{ old('issue_date', $admitCard->issue_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
            <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="issued" @selected(old('status', $admitCard->status) === 'issued')>{{ __('Issued') }}</option>
                <option value="revoked" @selected(old('status', $admitCard->status) === 'revoked')>{{ __('Revoked') }}</option>
            </select>
        </div>
    </div>
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Update') }}</button>
</form>
@endsection
