@extends('layouts.dashboard')

@section('title', __('Promote students') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Promote students') }}</h1>
        <a href="{{ route('dashboard.students') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back to list') }}</a>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
            {{ session('status') }}
        </div>
    @endif

    @include('dashboard.partials.form-errors')

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">{{ __('1. Select source students') }}</h2>
            <form method="get" action="{{ route('dashboard.students.promote') }}" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('From class') }} *</label>
                    <select name="from_class_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">{{ __('Select class') }}</option>
                        @foreach ($classes as $class)
                            <option value="{{ $class->id }}" @selected($class->id == $fromClassId)>{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('From section') }}</label>
                    <select name="from_section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">{{ __('All sections') }}</option>
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected($section->id == $fromSectionId)>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="rounded-lg bg-gray-800 px-4 py-2 text-sm font-medium text-white hover:bg-gray-900">
                    {{ __('Load students') }}
                </button>
            </form>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-lg font-semibold text-gray-800">{{ __('2. Choose target & promote') }}</h2>
            <form method="post" action="{{ route('dashboard.students.promote.store') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="from_class_id" value="{{ $fromClassId }}">
                <input type="hidden" name="from_section_id" value="{{ $fromSectionId }}">

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('To class') }} *</label>
                        <select name="to_class_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">{{ __('Select class') }}</option>
                            @foreach ($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('To section') }}</label>
                        <select name="to_section_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">{{ __('Optional') }}</option>
                            @foreach ($sections as $section)
                                <option value="{{ $section->id }}">{{ $section->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('To batch') }} *</label>
                        <select name="to_batch_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">{{ __('Select batch') }}</option>
                            @foreach ($batches as $batch)
                                <option value="{{ $batch->id }}">{{ $batch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <label class="flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="keep_roll_number" value="1" class="rounded border-gray-300">
                    {{ __('Keep existing roll numbers') }}
                </label>

                @if ($students->isNotEmpty())
                    <div class="border-t border-gray-100 pt-4">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="select-all" class="rounded border-gray-300">
                            {{ __('Select all students') }}
                        </label>
                        <div class="mt-3 max-h-64 space-y-1 overflow-y-auto">
                            @foreach ($students as $student)
                                <label class="flex items-center gap-2 rounded-md px-2 py-1 text-sm hover:bg-gray-50">
                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox rounded border-gray-300">
                                    <span>{{ $student->user->name ?? ('#'.$student->id) }}</span>
                                    <span class="text-gray-400">({{ __('Roll') }}: {{ $student->roll_number ?? '—' }})</span>
                                </label>
                            @endforeach
                        </div>
                        <label class="mt-3 flex items-center gap-2 text-sm text-gray-700">
                            <input type="checkbox" name="promote_all" value="1" class="rounded border-gray-300">
                            {{ __('Or promote ALL in source (ignore selection above)') }}
                        </label>
                    </div>
                @else
                    <p class="text-sm text-gray-500">{{ __('Select a source class and load students first.') }}</p>
                @endif

                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    {{ __('Promote') }}
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('select-all')?.addEventListener('change', function () {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = this.checked);
        });
    </script>
@endsection
