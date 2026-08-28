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
                <button
                    type="button"
                    onclick="document.getElementById('publish-summary-modal').classList.remove('hidden'); document.getElementById('publish-summary-modal').setAttribute('aria-hidden','false'); document.body.style.overflow='hidden';"
                    class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    @unless (auth()->user()?->can('review_exam_results')) disabled title="{{ __('You do not have permission to publish results.') }}" @endunless
                >{{ __('Publish all') }}</button>
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
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Actions') }}</th>
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
                                <td class="px-4 py-3">
                                    @if ($r)
                                        <a href="{{ route('dashboard.exams.results.marksheet', [$exam, $r]) }}" target="_blank" class="inline-flex items-center gap-1 rounded bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100">
                                            {{ __('Download') }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-10">
                                    <x-empty-state
                                        icon="document"
                                        :title="__('No students match this exam')"
                                        :message="__('Enter marks for students so results can be published.')"
                                    />
                                </td>
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

    <div id="publish-summary-modal" class="hidden" aria-hidden="true">
        <div class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-900/50 p-4" onclick="closePublishSummaryModal()">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="publish-summary-title">
                <h3 id="publish-summary-title" class="text-lg font-semibold text-gray-900">{{ __('Publish results') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Confirm the details before publishing. Once published, results become visible to students and parents.') }}</p>

                <dl class="mt-5 space-y-3 border-t border-gray-100 pt-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Exam') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $exam->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Class / Section') }}</dt>
                        <dd class="font-medium text-gray-900">
                            {{ collect([$exam->section?->name, $exam->batch?->name])->filter()->implode(' · ') ?: __('All') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Students in exam') }}</dt>
                        <dd class="font-medium text-gray-900">{{ number_format($students->count()) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Results entered') }}</dt>
                        <dd class="font-medium text-gray-900">{{ number_format((int) ($stats['participated'] ?? 0)) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-gray-500">{{ __('Guardians to notify') }}</dt>
                        <dd class="font-medium text-gray-900">{{ number_format((int) ($smsRecipients ?? 0)) }}</dd>
                    </div>
                </dl>

                @php $missing = $students->count() - (int) ($stats['participated'] ?? 0); @endphp
                @if ($missing > 0)
                    <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        {{ __(':n student(s) do not have marks yet. Publishing will expose only entered results.', ['n' => $missing]) }}
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <button type="button" onclick="closePublishSummaryModal()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ __('Cancel') }}</button>
                    <form method="post" action="{{ route('dashboard.exams.publish', $exam) }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">{{ __('Confirm publish') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function closePublishSummaryModal() {
            var m = document.getElementById('publish-summary-modal');
            m.classList.add('hidden');
            m.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        }
    </script>
@endsection
