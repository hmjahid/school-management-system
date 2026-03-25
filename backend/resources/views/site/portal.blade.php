@extends('layouts.app')

@section('title', site_ui('portal.page_title') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => site_ui('portal.hero_title'),
            'subtitle' => trim(site_ui('portal.hero_subtitle_prefix').' '.$user->name),
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

    <div class="grid gap-6 lg:grid-cols-2">
        @if($user->hasRole('student') && $student)
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_profile') }}</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ site_ui('portal.label_class') }}</dt><dd>{{ $student->class?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ site_ui('portal.label_section') }}</dt><dd>{{ $student->section?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ site_ui('portal.label_roll') }}</dt><dd>{{ $student->roll_number ?? $student->roll_no ?? '—' }}</dd></div>
                </dl>
            </section>
        @endif

        @if($user->hasRole('parent') && $children->isNotEmpty())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_linked') }}</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($children as $child)
                        <li class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $child->user?->name ?? site_ui('portal.fallback_student') }}</span>
                            <span class="text-gray-600"> — {{ $child->class?->name ?? '' }} {{ $child->section?->name ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_attendance') }}</h2>
            @if($recentAttendance->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ site_ui('portal.no_attendance') }}</p>
            @else
                <ul class="mt-4 max-h-64 space-y-2 overflow-y-auto text-sm">
                    @foreach ($recentAttendance as $row)
                        <li class="flex justify-between border-b border-gray-100 py-2">
                            <span class="text-gray-600">{{ $row->date?->format('M j, Y') }}</span>
                            <span class="font-medium text-gray-900">{{ $row->status }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_exams') }}</h2>
            @if($examResults->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ site_ui('portal.no_results') }}</p>
            @else
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($examResults as $r)
                        <li class="rounded border border-gray-100 px-3 py-2">
                            {{ str_replace([':m', ':g'], [$r->obtained_marks ?? '—', $r->grade ?? '—'], site_ui('portal.marks_line')) }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_progress') }}</h2>
            @if($examResults->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ site_ui('portal.no_results') }}</p>
            @else
                @php
                    $avg = $examResults->pluck('gpa')->filter(fn($v) => is_numeric($v))->avg();
                @endphp
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ site_ui('portal.progress_counted') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ $examResults->count() }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ site_ui('portal.progress_avg_gpa') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ $avg ? number_format((float)$avg, 2) : '—' }}</div>
                    </div>
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ site_ui('portal.progress_latest_grade') }}</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">{{ $examResults->first()->grade ?? '—' }}</div>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500">{{ site_ui('portal.progress_note') }}</p>
                <a href="{{ route('portal.progress') }}" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ site_ui('portal.progress_link') }} →</a>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_fees') }}</h2>
            @if($feePayments->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ site_ui('portal.no_fee_payments') }}</p>
            @else
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($feePayments->take(8) as $fp)
                        <li class="flex justify-between gap-2 border-b border-gray-100 py-2">
                            <span class="font-mono text-xs text-gray-700">{{ $fp->invoice_number }}</span>
                            <span class="text-right">
                                {{ number_format((float) $fp->paid_amount, 2) }} · {{ $fp->status }}
                                <a href="{{ route('site.payments.receipts.show', $fp) }}" class="ml-2 text-xs font-medium text-blue-600 hover:underline">{{ site_ui('payments.receipt') }}</a>
                            </span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('site.payments') }}" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ site_ui('portal.full_payment_portal') }} →</a>
            @endif
        </section>

        @if($user->hasRole('student') && $assignments->isNotEmpty())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_homework') }}</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($assignments as $a)
                        <li class="rounded-lg border border-gray-100 px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $a->title }}</span>
                            @if($a->due_date)
                                <span class="mt-1 block text-xs text-gray-500">{{ site_ui('portal.due_label') }}: {{ $a->due_date->format('M j, Y') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('portal.section_communication') }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ site_ui('portal.communication_intro') }}</p>

            @if(($announcements ?? collect())->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ site_ui('portal.no_announcements') }}</p>
            @else
                <ul class="mt-4 space-y-3">
                    @foreach ($announcements as $a)
                        <li class="rounded-lg border border-gray-100 bg-gray-50 p-4">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <div class="font-semibold text-gray-900">{{ $a->title }}</div>
                                <div class="text-xs text-gray-500">{{ ($a->starts_at ?? $a->created_at)?->format('M j, Y') }}</div>
                            </div>
                            @if($a->body)
                                <div class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $a->body }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
        </div>
    </div>
@endsection
