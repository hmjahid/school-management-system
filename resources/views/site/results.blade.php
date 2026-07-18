@extends('layouts.app')

@section('title', __('Results') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    @include('site.partials.inner-hero', [
        'title' => __('Results'),
        'subtitle' => __('Search published exam results by class, year, and roll number.'),
    ])

    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <form method="get" action="{{ route('site.results') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Class') }}</label>
                <select name="class_id" required class="admin-select">
                    <option value="">—</option>
                    @foreach($classes as $c)
                        <option value="{{ $c->id }}" @selected((string) request('class_id') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Year') }}</label>
                <select name="academic_session_id" required class="admin-select">
                    <option value="">—</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" @selected((string) request('academic_session_id') === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Roll number') }}</label>
                <input type="text" name="roll" required value="{{ request('roll') }}" class="admin-input">
            </div>
            <div class="flex items-end">
                <x-button type="submit" class="w-full">{{ __('Search') }}</x-button>
            </div>
        </form>

        @if(request()->filled(['class_id','academic_session_id','roll']))
            <div class="mt-8">
                @if($student && $result->isNotEmpty())
                    @php
                        $grouped = $result->groupBy(fn($r) => $r->exam?->name ?: __('Exam'));
                    @endphp
                    @foreach($grouped as $examName => $examResults)
                        <div class="mb-6">
                            <h3 class="mb-2 text-lg font-semibold text-slate-800">{{ $examName }}</h3>
                            <x-card :padding="false">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                                            <tr>
                                                <th class="px-4 py-3">{{ __('Subject') }}</th>
                                                <th class="px-4 py-3 text-right">{{ __('Marks') }}</th>
                                                <th class="px-4 py-3">{{ __('Grade') }}</th>
                                                <th class="px-4 py-3">{{ __('Remarks') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100">
                                            @foreach($examResults as $r)
                                                <tr>
                                                    <td class="px-4 py-2 font-medium text-slate-900">{{ $r->subject?->name ?? '—' }}</td>
                                                    <td class="px-4 py-2 text-right font-mono">{{ $r->obtained_marks ?? '—' }} / {{ $r->exam?->total_marks ?? '—' }}</td>
                                                    <td class="px-4 py-2">{{ $r->grade ?? '—' }}</td>
                                                    <td class="px-4 py-2 text-slate-600">{{ $r->remarks ?? '' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </x-card>
                        </div>
                    @endforeach
                @else
                    <x-card>
                        <p class="py-6 text-center text-sm text-slate-600">{{ __('No results found for the given roll number.') }}</p>
                    </x-card>
                @endif
            </div>
        @endif
    </div>
@endsection