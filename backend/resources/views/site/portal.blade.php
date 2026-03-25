@extends('layouts.app')

@section('title', __('Portal') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => __('Student / parent portal'),
            'subtitle' => __('Welcome, :name', ['name' => $user->name]),
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

    <div class="grid gap-6 lg:grid-cols-2">
        @if($user->hasRole('student') && $student)
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Your profile') }}</h2>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Class') }}</dt><dd>{{ $student->class?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Section') }}</dt><dd>{{ $student->section?->name ?? '—' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Roll') }}</dt><dd>{{ $student->roll_number ?? $student->roll_no ?? '—' }}</dd></div>
                </dl>
            </section>
        @endif

        @if($user->hasRole('parent') && $children->isNotEmpty())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Linked students') }}</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($children as $child)
                        <li class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $child->user?->name ?? __('Student') }}</span>
                            <span class="text-gray-600"> — {{ $child->class?->name ?? '' }} {{ $child->section?->name ?? '' }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Attendance') }}</h2>
            @if($recentAttendance->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ __('No recent attendance records.') }}</p>
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
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Exam results (published)') }}</h2>
            @if($examResults->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ __('No published results yet.') }}</p>
            @else
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($examResults as $r)
                        <li class="rounded border border-gray-100 px-3 py-2">
                            {{ __('Marks: :m (Grade :g)', ['m' => $r->obtained_marks ?? '—', 'g' => $r->grade ?? '—']) }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Fee status') }}</h2>
            @if($feePayments->isEmpty())
                <p class="mt-4 text-sm text-gray-600">{{ __('No fee payments recorded.') }}</p>
            @else
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($feePayments->take(8) as $fp)
                        <li class="flex justify-between gap-2 border-b border-gray-100 py-2">
                            <span class="font-mono text-xs text-gray-700">{{ $fp->invoice_number }}</span>
                            <span>{{ number_format((float) $fp->paid_amount, 2) }} · {{ $fp->status }}</span>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('site.payments') }}" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Full payment portal') }} →</a>
            @endif
        </section>

        @if($user->hasRole('student') && $assignments->isNotEmpty())
            <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Homework / assignments') }}</h2>
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($assignments as $a)
                        <li class="rounded-lg border border-gray-100 px-4 py-3">
                            <span class="font-medium text-gray-900">{{ $a->title }}</span>
                            @if($a->due_date)
                                <span class="mt-1 block text-xs text-gray-500">{{ __('Due') }}: {{ $a->due_date->format('M j, Y') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <section class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
            <h2 class="text-lg font-semibold text-gray-900">{{ __('Communication') }}</h2>
            <p class="mt-2 text-sm text-gray-600">{{ __('Messaging with teachers uses the school’s notification system. Enable email/SMS in your profile and watch for announcements from the dashboard team.') }}</p>
        </section>
    </div>
        </div>
    </div>
@endsection
