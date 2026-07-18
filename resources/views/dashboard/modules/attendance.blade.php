@extends('layouts.dashboard')

@section('title', __('Attendance') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Attendance') }}</h1>
        <div class="flex flex-wrap items-end gap-2">
        @can('create', App\Models\Attendance::class)
            <a href="{{ route('dashboard.attendance.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Record attendance') }}</a>
        @endcan
        <form method="get" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Date') }}</label>
                <input type="date" name="date" value="{{ request('date') }}"
                    class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Status') }}</label>
                <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">{{ __('Any') }}</option>
                    <option value="present" @selected(request('status') === 'present')>{{ __('Present') }}</option>
                    <option value="absent" @selected(request('status') === 'absent')>{{ __('Absent') }}</option>
                    <option value="late" @selected(request('status') === 'late')>{{ __('Late') }}</option>
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
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Date') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Student') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Subject') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($records as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-700">{{ optional($row->date)->format('Y-m-d') ?? '—' }}</td>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $row->student?->user?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $row->subject?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium capitalize text-gray-800">{{ str_replace('_', ' ', $row->status ?? '—') }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No attendance records.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($records->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
