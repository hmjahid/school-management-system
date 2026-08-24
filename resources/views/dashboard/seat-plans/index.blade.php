@extends('layouts.dashboard')
@section('title', __('Seat plans') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Seat plans') }}</h1>
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <select name="published" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All exams') }}</option>
        <option value="1" @selected(request('published') === '1')>{{ __('Published only') }}</option>
        <option value="0" @selected(request('published') === '0')>{{ __('Unpublished only') }}</option>
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left font-semibold text-gray-600">#</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Exam') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Batch') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Section') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Date') }}</th><th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th></tr></thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($exams as $exam)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $exam->id }}</td>
                    <td class="px-4 py-3">{{ $exam->name }}</td>
                    <td class="px-4 py-3">{{ $exam->batch?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $exam->section?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $exam->start_date?->format('d M Y') ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.seat-plans.generate', [$exam, 'view' => 1]) }}" target="_blank" class="text-green-600 hover:text-green-800">{{ __('Preview') }}</a>
                        <a href="{{ route('dashboard.seat-plans.generate', $exam) }}" class="ml-2 text-blue-600 hover:text-blue-800">{{ __('Generate Seat Plan') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('No exams found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $exams->links() }}</div>
@endsection
