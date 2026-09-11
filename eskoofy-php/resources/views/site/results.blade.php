@extends('layouts.app')

@section('title', __('Results') . ' — ' . ($siteSettings->site_name ?? config('app.name')))
@section('meta_description', __('Search published exam results by class, year, and roll number.'))

@section('content')
    @if($siteSettings->section_visibility['results_hero'] ?? true)
    <div class="bg-gradient-to-br from-slate-900 via-blue-900 to-indigo-900 py-20 text-white">
        <div class="mx-auto max-w-7xl px-4 text-center sm:px-6 lg:px-8">
            <h1 class="text-4xl font-bold md:text-5xl">{{ __('Results') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-lg text-blue-100">{{ __('Search published exam results by class, year, and roll number.') }}</p>
        </div>
    </div>
    @endif

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        @if($siteSettings->section_visibility['results_form'] ?? true)
        {{-- Search form --}}
        <div class="mx-auto max-w-3xl reveal">
            <form method="get" action="{{ route('site.results') }}" class="rounded-2xl border border-slate-200 bg-white p-8 shadow-lg">
                <div class="grid gap-5 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Class') }}</label>
                        <select name="class_id" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">—</option>
                            @foreach($classes as $c)
                                <option value="{{ $c->id }}" @selected((string) request('class_id') === (string) $c->id)>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Session') }}</label>
                        <select name="academic_session_id" required class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                            <option value="">—</option>
                            @foreach($sessions as $s)
                                <option value="{{ $s->id }}" @selected((string) request('academic_session_id') === (string) $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-600">{{ __('Roll Number') }}</label>
                        <input type="text" name="roll" required value="{{ request('roll') }}" placeholder="e.g. 101"
                            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                    </div>
                </div>
                <div class="mt-6 text-center">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-10 py-3.5 text-sm font-semibold text-white shadow-lg shadow-blue-600/20 transition-all hover:bg-blue-700 hover:shadow-xl">
                        <svg class="h-5 w-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        {{ __('Search Results') }}
                    </button>
                </div>
            </form>
        </div>
        @endif

        {{-- Results display --}}
        @if(request()->filled(['class_id','academic_session_id','roll']))
            <div class="mt-10 reveal">
                @if($student && $result->isNotEmpty())
                    @php
                        $grouped = $result->groupBy(fn($r) => $r->exam?->name ?: __('Exam'));
                        $allMarks = $result->pluck('obtained_marks')->filter();
                        $totalObtained = $allMarks->sum();
                        $totalMax = $result->sum(fn($r) => $r->exam?->total_marks ?? 0);
                        $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 1) : 0;
                        $grade = $percentage >= 80 ? 'A+' : ($percentage >= 70 ? 'A' : ($percentage >= 60 ? 'A-' : ($percentage >= 50 ? 'B' : ($percentage >= 40 ? 'C' : 'F'))));
                    @endphp

                    {{-- Student info card --}}
                    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-4">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-indigo-100 text-2xl font-bold text-blue-600">
                                    {{ \Illuminate\Support\Str::substr($student->user?->name ?? 'S', 0, 1) }}
                                </div>
                                <div>
                                    <h2 class="text-xl font-bold text-slate-900">{{ $student->user?->name ?? __('Student') }}</h2>
                                    <p class="text-sm text-slate-500">{{ $student->class?->name ?? '' }} · {{ __('Roll') }}: {{ $student->roll_number ?? $student->roll_no ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                {{-- Percentage donut --}}
                                <div class="relative h-20 w-20">
                                    <svg class="h-20 w-20 -rotate-90" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"></circle>
                                        <circle cx="18" cy="18" r="15.5" fill="none" stroke="{{ $percentage >= 60 ? '#22c55e' : ($percentage >= 40 ? '#eab308' : '#ef4444') }}" stroke-width="3" stroke-dasharray="{{ $percentage * 0.865 }} 100" stroke-linecap="round"></circle>
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-lg font-bold text-slate-900">{{ $percentage }}%</span>
                                </div>
                                <div class="text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full px-4 py-1.5 text-sm font-bold {{ $grade === 'A+' || $grade === 'A' ? 'bg-green-100 text-green-800' : ($grade === 'F' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        {{ __('Grade') }}: {{ $grade }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Subject-wise marks tables --}}
                    @foreach($grouped as $examName => $examResults)
                        <div class="mt-8">
                            <h3 class="mb-3 text-lg font-semibold text-slate-800">{{ $examName }}</h3>
                            <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
                                            <tr>
                                                <th class="px-5 py-3.5">{{ __('Subject') }}</th>
                                                <th class="px-5 py-3.5 text-right">{{ __('Marks') }}</th>
                                                <th class="px-5 py-3.5 text-center">{{ __('Grade') }}</th>
                                                <th class="px-5 py-3.5">{{ __('Remarks') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach($examResults as $r)
                                                <tr class="hover:bg-slate-50 transition-colors">
                                                    <td class="px-5 py-3 font-medium text-slate-900">{{ $r->subject?->name ?? '—' }}</td>
                                                    <td class="px-5 py-3 text-right font-mono">
                                                        <span class="{{ ($r->obtained_marks ?? 0) < (($r->exam?->total_marks ?? 100) * 0.4) ? 'text-red-600 font-semibold' : 'text-slate-900' }}">
                                                            {{ $r->obtained_marks ?? '—' }}
                                                        </span>
                                                        <span class="text-slate-400">/ {{ $r->exam?->total_marks ?? '—' }}</span>
                                                    </td>
                                                    <td class="px-5 py-3 text-center">
                                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ($r->grade ?? '') === 'A+' || ($r->grade ?? '') === 'A' ? 'bg-green-100 text-green-800' : (($r->grade ?? '') === 'F' ? 'bg-red-100 text-red-800' : 'bg-slate-100 text-slate-700') }}">
                                                            {{ $r->grade ?? '—' }}
                                                        </span>
                                                    </td>
                                                    <td class="px-5 py-3 text-slate-500">{{ $r->remarks ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Print & Download buttons --}}
                    <div class="mt-10 flex flex-wrap gap-3">
                        <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 shadow-sm transition-all hover:bg-slate-50 hover:shadow-md">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            {{ __('Print Result') }}
                        </button>
                        <a href="{{ route('site.results.download', ['class_id' => request('class_id'), 'academic_session_id' => request('academic_session_id'), 'roll' => request('roll')]) }}" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            {{ __('Download PDF') }}
                        </a>
                    </div>
                @else
                    <div class="rounded-2xl border-2 border-dashed border-slate-200 p-16 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <p class="mt-4 text-sm text-slate-500">{{ __('No results found for the given roll number.') }}</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection
