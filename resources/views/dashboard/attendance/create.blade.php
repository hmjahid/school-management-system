@extends('layouts.dashboard')

@section('title', __('Record attendance') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Record attendance') }}</h1>
        <a href="{{ route('dashboard.attendance') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>
    @include('dashboard.partials.form-errors')
    @if ($errors->has('attendance'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $errors->first('attendance') }}</div>
    @endif
    <form method="post" action="{{ route('dashboard.attendance.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Date') }} *</label>
                <input type="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Academic session') }} *</label>
                <select name="academic_session_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($sessions as $s)
                        <option value="{{ $s->id }}" @selected(old('academic_session_id') == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Student') }} *</label>
                <select name="student_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($students as $st)
                        <option value="{{ $st->id }}" @selected(old('student_id') == $st->id)>{{ $st->user?->name }} ({{ $st->class?->name }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Teacher') }} *</label>
                <select name="teacher_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($teachers as $t)
                        <option value="{{ $t->id }}" @selected(old('teacher_id') == $t->id)>{{ $t->user?->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($statuses as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', \App\Models\Attendance::STATUS_PRESENT) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Type') }} *</label>
                <select name="type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($types as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', \App\Models\Attendance::TYPE_DAILY) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Batch') }} <span class="text-gray-400">({{ __('or section') }})</span></label>
                <select name="batch_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($batches as $b)
                        <option value="{{ $b->id }}" @selected(old('batch_id') == $b->id)>{{ $b->name ?? $b->code ?? ('#'.$b->id) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Section') }}</label>
                <select name="section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($sections as $sec)
                        <option value="{{ $sec->id }}" @selected(old('section_id') == $sec->id)>{{ $sec->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('Subject') }} <span class="text-gray-400">({{ __('optional') }})</span></label>
                <select name="subject_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($subjects as $sub)
                        <option value="{{ $sub->id }}" @selected(old('subject_id') == $sub->id)>{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">{{ __('Remarks') }}</label>
                <input name="remarks" value="{{ old('remarks') }}" maxlength="500" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
        </div>
        <p class="text-sm text-gray-600">{{ __('Provide either a batch or a section (not both required by the server).') }}</p>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save attendance') }}</button>
    </form>
@endsection
