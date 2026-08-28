@extends('layouts.dashboard')

@section('title', __('dashboard.my_results') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('dashboard.my_results') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Manage your exams: enter marks, then publish when every student has a result.') }}</p>
        </div>
        <a href="{{ route('dashboard.exams') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('All exams') }} →</a>
    </div>

    @php
        $columns = [
            'pending' => ['label' => __('Needs marks entry'), 'color' => 'amber', 'empty' => __('No pending exams.')],
            'ready' => ['label' => __('Ready to publish'), 'color' => 'blue', 'empty' => __('Nothing ready to publish yet.')],
            'published' => ['label' => __('Published'), 'color' => 'emerald', 'empty' => __('No published exams yet.')],
        ];
        $groups = compact('pending', 'ready', 'published');
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        @foreach ($columns as $key => $col)
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="flex items-center justify-between text-sm font-semibold text-gray-900">
                        <span class="inline-flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-{{ $col['color'] }}-500"></span>
                            {{ $col['label'] }}
                        </span>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ $groups[$key]->count() }}</span>
                    </h2>
                </div>
                <div class="space-y-3 p-4">
                    @forelse ($groups[$key] as $exam)
                        <div class="rounded-lg border border-gray-100 bg-gray-50/50 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $exam->name }}</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $exam->subject?->name ?? '—' }}
                                        @if ($exam->section?->name || $exam->batch?->name)
                                            ·
                                            {{ $exam->section?->name ?? '' }}{{ $exam->batch?->name ? ' / '.$exam->batch->name : '' }}
                                        @endif
                                    </p>
                                </div>
                                <span class="text-xs font-medium text-gray-500">{{ $exam->total_marks ?? '?' }} {{ __('marks') }}</span>
                            </div>

                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-200">
                                @php $pct = $exam->total_students > 0 ? min(100, (int) round($exam->results_count / $exam->total_students * 100)) : 0; @endphp
                                <div class="h-full rounded-full bg-{{ $col['color'] }}-500" style="width: {{ $pct }}%"></div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('Marked') }}: {{ $exam->results_count }}/{{ $exam->total_students }}
                            </p>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <a href="{{ route('dashboard.exams.results', $exam) }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold {{ $key === 'pending' ? 'bg-amber-600 text-white hover:bg-amber-700' : ($key === 'ready' ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200') }}">
                                    {{ $key === 'pending' ? __('Enter marks') : ($key === 'ready' ? __('Review & publish') : __('View')) }}
                                </a>
                                @if ($key === 'ready')
                                    <form method="post" action="{{ route('dashboard.exams.publish', $exam) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">{{ __('Publish') }}</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="py-6">
                            <x-empty-state :title="$col['empty']" />
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>
@endsection