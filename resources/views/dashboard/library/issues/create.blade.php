@extends('layouts.dashboard')
@section('title', __('dashboard.issue_book') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.issue_book') }}</h1>
    <a href="{{ route('dashboard.library.issues.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.library.issues.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.title') }} *</label>
            <select name="book_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">{{ __('Select book') }}</option>
                @foreach($books as $book)
                    <option value="{{ $book->id }}" @selected(old('book_id') == $book->id)>{{ $book->title }} ({{ $book->available_quantity }} {{ __('available') }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Borrower type') }}</label>
            <div class="mt-2 flex gap-4">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="borrower_type" value="student" @checked(old('borrower_type', 'student') === 'student') onchange="toggleBorrower()" class="text-blue-600">
                    {{ __('Student') }}
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="radio" name="borrower_type" value="teacher" @checked(old('borrower_type') === 'teacher') onchange="toggleBorrower()" class="text-blue-600">
                    {{ __('Teacher') }}
                </label>
            </div>
        </div>
        <div id="studentSelect">
            <label class="block text-sm font-medium text-gray-700">{{ __('Student') }}</label>
            <select name="student_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">{{ __('Select student') }}</option>
                @foreach($students as $s)
                    <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>{{ trim($s->first_name . ' ' . $s->last_name) }}</option>
                @endforeach
            </select>
        </div>
        <div id="teacherSelect" class="hidden">
            <label class="block text-sm font-medium text-gray-700">{{ __('Teacher') }}</label>
            <select name="teacher_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="">{{ __('Select teacher') }}</option>
                @foreach($teachers as $t)
                    <option value="{{ $t->id }}" @selected(old('teacher_id') == $t->id)>{{ $t->user?->name ?? $t->employee_id }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.issue_date') }} *</label>
            <input type="date" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('dashboard.due_date') }} *</label>
            <input type="date" name="due_date" value="{{ old('due_date', date('Y-m-d', strtotime('+' . $settings->issue_duration_days . ' days'))) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Notes') }}</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('notes') }}</textarea>
    </div>
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
</form>

<script>
function toggleBorrower() {
    var type = document.querySelector('input[name="borrower_type"]:checked').value;
    document.getElementById('studentSelect').classList.toggle('hidden', type !== 'student');
    document.getElementById('teacherSelect').classList.toggle('hidden', type !== 'teacher');
}
toggleBorrower();
</script>
@endsection
