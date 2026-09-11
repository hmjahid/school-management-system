@extends('layouts.app')

@section('title', site_ui('portal_progress.page_title') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ site_ui('portal_progress.heading') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ site_ui('portal_progress.intro') }}</p>
            </div>
            <a href="{{ route('portal') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ site_ui('portal_progress.back') }}</a>
        </div>

        <form method="get" class="mt-8 grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ site_ui('portal_progress.student') }}</label>
                <select name="student_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    @foreach($studentIds as $sid)
                        <option value="{{ $sid }}" @selected((int) request('student_id', $selectedStudentId) === (int) $sid)>{{ str_replace(':id', (string) $sid, site_ui('portal_progress.student_num')) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ site_ui('portal_progress.academic_session') }}</label>
                <select name="academic_session_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ site_ui('portal_progress.all') }}</option>
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" @selected((string) request('academic_session_id') === (string) $s->id)>{{ $s->name ?? ('#' . $s->id) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ site_ui('portal_progress.exam_type') }}</label>
                <select name="exam_type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">{{ site_ui('portal_progress.all') }}</option>
                    @foreach($examTypes as $t)
                        <option value="{{ $t }}" @selected(request('exam_type') === $t)>{{ ucfirst(str_replace('_', ' ', $t)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-3 flex items-center justify-between gap-3">
                <button class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-black">{{ site_ui('portal_progress.apply_filters') }}</button>
                <a href="{{ route('portal.progress') }}" class="text-sm font-medium text-gray-600 hover:text-gray-900">{{ site_ui('portal_progress.reset') }}</a>
            </div>
        </form>

        <section class="mt-8">
            @if($examSummaries->isEmpty())
                <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-700 shadow-sm">
                    {{ site_ui('portal_progress.no_results') }}
                </div>
            @else
                <div class="space-y-6">
                    @foreach($examSummaries as $summary)
                        @php($exam = $summary['exam'])
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">{{ $exam->name ?? site_ui('portal_progress.exam') }}</h2>
                                    <div class="mt-1 text-sm text-gray-600">
                                        {{ str_replace(':t', ucfirst(str_replace('_', ' ', $exam->type ?? '—')), site_ui('portal_progress.type_colon')) }}
                                        ·
                                        {{ str_replace(':n', (string) $summary['count'], site_ui('portal_progress.results_colon')) }}
                                    </div>
                                </div>
                                <div class="text-sm text-gray-700">
                                    <div>{{ str_replace(':m', $summary['avg_marks'] ? number_format((float) $summary['avg_marks'], 2) : '—', site_ui('portal_progress.avg_marks')) }}</div>
                                    <div>{{ str_replace(':g', $summary['avg_grade_point'] ? number_format((float) $summary['avg_grade_point'], 2) : '—', site_ui('portal_progress.avg_gp')) }}</div>
                                </div>
                            </div>

                            <div class="mt-4 overflow-x-auto rounded-lg border border-gray-100">
                                <table class="min-w-full divide-y divide-gray-100 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('portal_progress.marks') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('portal_progress.grade') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('portal_progress.grade_point') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('portal_progress.status') }}</th>
                                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('portal_progress.remarks') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($summary['rows'] as $r)
                                            <tr>
                                                <td class="px-4 py-3 text-gray-900">{{ $r->obtained_marks ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-900">{{ $r->grade ?? '—' }}</td>
                                                <td class="px-4 py-3 text-gray-900">{{ $r->grade_point ?? '—' }}</td>
                                                <td class="px-4 py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $r->status_badge }}">{{ $r->status_label }}</span></td>
                                                <td class="px-4 py-3 text-gray-700">{{ $r->remarks ?? '—' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
@endsection
