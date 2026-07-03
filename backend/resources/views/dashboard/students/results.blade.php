@extends('layouts.dashboard')

@section('title', __('Results') . ' — ' . $student->user?->name)

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('dashboard.students.show', $student) }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">← {{ $student->user?->name }}</a>
            <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ __('Exam results') }}</h1>
            <p class="mt-1 text-sm text-gray-600">
                {{ $student->user?->name }} ·
                {{ $student->class?->name ?? '—' }} ·
                {{ $student->section?->name ?? '—' }}
            </p>
        </div>
    </div>

    <div class="mb-6 grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Total published') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ number_format($summary['count'] ?? 0) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Average grade point') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['avg_grade_point'] ? number_format((float) $summary['avg_grade_point'], 2) : '—' }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium uppercase text-gray-500">{{ __('Latest grade') }}</p>
            <p class="mt-1 text-2xl font-bold text-gray-900">{{ $summary['latest_grade'] ?? '—' }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Exam') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Subject') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Marks') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Grade') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Published') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($results as $r)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $r->exam?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $r->exam?->subject?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-right text-gray-700">{{ $r->obtained_marks !== null ? number_format((float) $r->obtained_marks, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-gray-900">{{ $r->grade ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">{{ $r->status_label ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ optional($r->published_at)->format('Y-m-d') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('No published results yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($results->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $results->links() }}</div>
        @endif
    </div>
@endsection
