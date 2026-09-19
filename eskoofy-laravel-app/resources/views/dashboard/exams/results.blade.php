@extends('layouts.dashboard')

@section('title', __('Results') . ' — ' . $exam->name)

@section('content')
    @php $canReview = auth()->user()?->can('review_exam_results'); @endphp

    <x-page-header :title="$exam->name" :description="collect([
        $exam->subject?->name,
        __('Total marks').': '.($exam->total_marks ?? '—'),
        __('Passing').': '.($exam->passing_marks ?? '—'),
    ])->implode(' · ')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Exams'), 'url' => route('dashboard.exams')],
                ['label' => $exam->name],
            ]" />
        </x-slot:breadcrumbs>
        @if ($stats['total_students'] > 0)
            <x-slot:actions>
                <div class="flex flex-wrap gap-2">
                    <x-button :href="route('dashboard.exams.results.export', $exam)" variant="secondary" size="sm">{{ __('Export CSV') }}</x-button>
                    <x-button variant="danger" size="sm" data-open-publish-summary @unless ($canReview) disabled title="{{ __('You do not have permission to publish results.') }}" @endunless>
                        {{ __('Publish all') }}
                    </x-button>
                    <form method="post" action="{{ route('dashboard.exams.unpublish', $exam) }}"
                        data-confirm="{{ __('Unpublish all results? Students and parents will no longer see them.') }}"
                        data-confirm-title="{{ __('Unpublish results') }}" data-confirm-label="{{ __('Unpublish') }}">
                        @csrf
                        <x-button type="submit" variant="secondary" size="sm">{{ __('Unpublish all') }}</x-button>
                    </form>
                </div>
            </x-slot:actions>
        @endif
    </x-page-header>

    @php
        $hasPublished = $results->where('is_published', true)->isNotEmpty();
    @endphp

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <x-card :padding="false">
            <div class="px-4 py-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Students') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($students->count()) }}</p>
            </div>
        </x-card>
        <x-card :padding="false">
            <div class="px-4 py-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Results entered') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format($stats['participated']) }}</p>
            </div>
        </x-card>
        <x-card :padding="false">
            <div class="px-4 py-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Pass rate') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['pass_rate'] ?? 0 }}%</p>
            </div>
        </x-card>
        <x-card :padding="false">
            <div class="px-4 py-4">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('Average score') }}</p>
                <p class="mt-1 text-2xl font-bold text-slate-900 dark:text-slate-100">{{ number_format((float) ($stats['average_score'] ?? 0), 1) }}</p>
            </div>
        </x-card>
    </div>

    <form method="post" action="{{ route('dashboard.exams.results.store', $exam) }}" class="space-y-4">
        @csrf
        <x-card :padding="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600 dark:bg-slate-800/60 dark:text-slate-300">
                        <tr>
                            <th class="px-4 py-3.5 font-semibold">#</th>
                            <th class="px-4 py-3.5 font-semibold">{{ __('Student') }}</th>
                            <th class="px-4 py-3.5 font-semibold">{{ __('Class') }}</th>
                            <th class="px-4 py-3.5 font-semibold">{{ __('Roll') }}</th>
                            <th class="px-4 py-3.5 text-right font-semibold">{{ __('Marks') }} / {{ $exam->total_marks ?? '?' }}</th>
                            <th class="px-4 py-3.5 font-semibold">{{ __('Grade') }}</th>
                            <th class="px-4 py-3.5 font-semibold">{{ __('Status') }}</th>
                            <th class="px-4 py-3.5 font-semibold">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-800">
                        @forelse ($students as $i => $student)
                            @php $r = $results->get($student->id); @endphp
                            <tr class="admin-table-row">
                                <td class="px-4 py-3 text-slate-500 dark:text-slate-400">{{ $i + 1 }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900 dark:text-slate-100">
                                    {{ $student->user?->name }}
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ $student->admission_number }}</div>
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $student->class?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $student->roll_number ?? '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <input
                                        type="number"
                                        name="marks[{{ $student->id }}]"
                                        value="{{ old('marks.' . $student->id, $r?->obtained_marks) }}"
                                        min="0"
                                        max="{{ $exam->total_marks ?: 100 }}"
                                        step="0.01"
                                        class="w-24 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-right text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100"
                                    >
                                </td>
                                <td class="px-4 py-3 text-slate-700 dark:text-slate-300">{{ $r?->grade ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    @if ($r)
                                        <x-badge :variant="$r->is_published ? 'success' : 'default'">{{ $r->is_published ? __('Published') : __('Draft') }}</x-badge>
                                    @else
                                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('Not entered') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($r)
                                        <a href="{{ route('dashboard.exams.results.marksheet', [$exam, $r]) }}" target="_blank" class="inline-flex items-center gap-1 rounded bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700 hover:bg-brand-100 dark:bg-brand-900/30 dark:text-brand-300">
                                            {{ __('Download') }}
                                        </a>
                                    @else
                                        <span class="text-xs text-slate-300 dark:text-slate-600">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-12">
                                    <x-empty-state
                                        icon="document"
                                        :title="__('No students match this exam')"
                                        :message="__('Enter marks for students so results can be published.')"
                                        :cta="['label' => __('All exams'), 'url' => route('dashboard.exams')]"
                                    />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        @if ($students->isNotEmpty())
            <div class="flex justify-end">
                <x-button type="submit">{{ __('Save marks') }}</x-button>
            </div>
        @endif
    </form>

    <div id="publish-summary-modal" class="hidden" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="publish-summary-title">
        <div class="modal-backdrop" data-publish-backdrop></div>
        <div class="fixed inset-0 z-[91] flex items-center justify-center p-4">
            <div class="modal-panel">
                <h3 id="publish-summary-title" class="text-lg font-semibold text-slate-900">{{ __('Publish results') }}</h3>
                <p class="mt-1 text-sm text-slate-600">{{ __('Confirm the details before publishing. Once published, results become visible to students and parents.') }}</p>

                <dl class="mt-5 space-y-3 border-t border-slate-100 pt-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Exam') }}</dt>
                        <dd class="font-medium text-slate-900">{{ $exam->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Class / Section') }}</dt>
                        <dd class="font-medium text-slate-900">
                            {{ collect([$exam->section?->name, $exam->batch?->name])->filter()->implode(' · ') ?: __('All') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Students in exam') }}</dt>
                        <dd class="font-medium text-slate-900">{{ number_format($students->count()) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Results entered') }}</dt>
                        <dd class="font-medium text-slate-900">{{ number_format((int) ($stats['participated'] ?? 0)) }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-slate-500">{{ __('Guardians to notify') }}</dt>
                        <dd class="font-medium text-slate-900">{{ number_format((int) ($smsRecipients ?? 0)) }}</dd>
                    </div>
                </dl>

                @php $missing = $students->count() - (int) ($stats['participated'] ?? 0); @endphp
                @if ($missing > 0)
                    <p class="mt-4 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        {{ __(':n student(s) do not have marks yet. Publishing will expose only entered results.', ['n' => $missing]) }}
                    </p>
                @endif

                <div class="mt-6 flex flex-wrap justify-end gap-2">
                    <x-button variant="secondary" size="sm" data-publish-cancel>{{ __('Cancel') }}</x-button>
                    <form method="post" action="{{ route('dashboard.exams.publish', $exam) }}" data-publish-form>
                        @csrf
                        <x-button type="submit" variant="danger" size="sm">{{ __('Confirm publish') }}</x-button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var modal = document.getElementById('publish-summary-modal');
            if (!modal) return;
            var openBtn = document.querySelector('[data-open-publish-summary]');
            var cancelBtn = modal.querySelector('[data-publish-cancel]');
            var backdrop = modal.querySelector('[data-publish-backdrop]');

            function open() {
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
                cancelBtn.focus();
            }
            function close() {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }
            openBtn?.addEventListener('click', open);
            cancelBtn?.addEventListener('click', close);
            backdrop?.addEventListener('click', close);
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.classList.contains('hidden')) close(); });
        })();
    </script>
@endsection