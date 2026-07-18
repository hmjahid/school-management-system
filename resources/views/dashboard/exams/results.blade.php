@extends('layouts.dashboard')

@section('title', __('Results') . ' — ' . $exam->name)

@section('content')
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('dashboard.exams') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ __('All exams') }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $exam->name }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $exam->subject?->name ?? '—' }} ·
                {{ __('Total marks') }}: {{ $exam->total_marks ?? '—' }} ·
                {{ __('Passing') }}: {{ $exam->passing_marks ?? '—' }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if ($stats['total_students'] > 0)
                <a href="{{ route('dashboard.exams.results.export', $exam) }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">{{ __('Export CSV') }}</a>
                <form method="post" action="{{ route('dashboard.exams.publish', $exam) }}" onsubmit="return confirm({{ json_encode(__('Publish all results?')) }});">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">{{ __('Publish all') }}</button>
                </form>
                <form method="post" action="{{ route('dashboard.exams.unpublish', $exam) }}" onsubmit="return confirm({{ json_encode(__('Unpublish all?')) }});">
                    @csrf
                    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">{{ __('Unpublish all') }}</button>
                </form>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @php
        $hasPublished = $results->where('is_published', true)->isNotEmpty();
    @endphp

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Students') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($students->count()) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Results entered') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($stats['participated']) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Pass rate') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $stats['pass_rate'] ?? 0 }}%</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Average score') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format((float) ($stats['average_score'] ?? 0), 1) }}</p>
        </div>
    </div>

    <form method="post" action="{{ route('dashboard.exams.results.store', $exam) }}" class="space-y-4">
        @csrf
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">#</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Student') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Class') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Roll') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Marks') }} / {{ $exam->total_marks ?? '?' }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Grade') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($students as $i => $student)
                            @php $r = $results->get($student->id); @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-gray-500">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    {{ $student->user?->name }}
                                    <div class="text-xs text-gray-500">{{ $student->admission_number }}</div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $student->class?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $student->roll_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <input
                                        type="number"
                                        name="marks[{{ $student->id }}]"
                                        value="{{ old('marks.' . $student->id, $r?->obtained_marks) }}"
                                        min="0"
                                        max="{{ $exam->total_marks ?: 100 }}"
                                        step="0.01"
                                        class="w-24 rounded-lg border border-gray-300 px-2 py-1.5 text-right text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    >
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $r?->grade ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($r)
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                            {{ $r->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                            {{ $r->is_published ? __('Published') : __('Draft') }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('Not entered') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">{{ __('No students match this exam.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if ($students->isNotEmpty())
            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save marks') }}</button>
            </div>
        @endif
    </form>
@endsection
