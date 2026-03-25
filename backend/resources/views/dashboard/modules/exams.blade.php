@extends('layouts.dashboard')

@section('title', __('Exams') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Exams') }}</h1>
        <div class="flex flex-wrap items-end gap-2">
        @can('create', App\Models\Exam::class)
            <a href="{{ route('dashboard.exams.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Schedule exam') }}</a>
        @endcan
        <form method="get" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Status') }}</label>
                <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ __('Any') }}</option>
                    <option value="upcoming" @selected(request('status') === 'upcoming')>{{ __('Upcoming') }}</option>
                    <option value="ongoing" @selected(request('status') === 'ongoing')>{{ __('Ongoing') }}</option>
                    <option value="completed" @selected(request('status') === 'completed')>{{ __('Completed') }}</option>
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ __('Filter') }}</button>
        </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Title') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Subject') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Starts') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($exams as $exam)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $exam->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $exam->subject?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ optional($exam->start_date)->format('Y-m-d H:i') ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $exam->status ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No exams found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($exams->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $exams->links() }}</div>
        @endif
    </div>
@endsection
