@extends('layouts.dashboard')

@section('title', __('Schedule exam') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Schedule exam') }}</h1>
        <a href="{{ route('dashboard.exams') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>
    @include('dashboard.partials.form-errors')
    <form method="post" action="{{ route('dashboard.exams.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2"><label class="block text-sm font-medium text-gray-700">{{ __('Exam name') }} *</label><input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Code') }} *</label><input name="code" value="{{ old('code') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. MID-2025-01"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Type') }} *</label>
                <select name="type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($examTypes as $val => $label)
                        <option value="{{ $val }}" @selected(old('type', array_key_first($examTypes)) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label>
                <select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($examStatuses as $val => $label)
                        <option value="{{ $val }}" @selected(old('status', \App\Models\Exam::STATUS_DRAFT) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
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
                <label class="block text-sm font-medium text-gray-700">{{ __('Subject') }} *</label>
                <select name="subject_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('Select') }}</option>
                    @foreach ($subjects as $sub)
                        <option value="{{ $sub->id }}" @selected(old('subject_id') == $sub->id)>{{ $sub->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Batch') }}</label>
                <select name="batch_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ __('None') }}</option>
                    @foreach ($batches as $b)
                        <option value="{{ $b->id }}" @selected(old('batch_id') == $b->id)>{{ $b->name ?? $b->code ?? $b->id }}</option>
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
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Start date') }} *</label><input type="date" name="start_date" value="{{ old('start_date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('End date') }} *</label><input type="date" name="end_date" value="{{ old('end_date', now()->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Duration (minutes)') }} *</label><input type="number" name="duration" value="{{ old('duration', 60) }}" min="1" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Total marks') }} *</label><input type="number" step="0.01" name="total_marks" value="{{ old('total_marks', 100) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Passing marks') }} *</label><input type="number" step="0.01" name="passing_marks" value="{{ old('passing_marks', 40) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Grading type') }} *</label>
                <select name="grading_type" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach ($gradingTypes as $val => $label)
                        <option value="{{ $val }}" @selected(old('grading_type', array_key_first($gradingTypes)) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-sm font-medium text-gray-700">{{ __('Weight %') }} *</label><input type="number" step="0.01" name="weightage" value="{{ old('weightage', 100) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
            <div class="flex items-center gap-2 sm:col-span-2">
                <input type="hidden" name="is_published" value="0">
                <input type="checkbox" name="is_published" value="1" id="pub" class="rounded border-gray-300 text-blue-600" @checked(old('is_published'))>
                <label for="pub" class="text-sm font-medium text-gray-700">{{ __('Published') }}</label>
            </div>
        </div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label><textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description') }}</textarea></div>
        <p class="text-sm text-gray-600">{{ __('Provide either a batch or a section.') }}</p>
        <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Create exam') }}</button>
    </form>
@endsection
