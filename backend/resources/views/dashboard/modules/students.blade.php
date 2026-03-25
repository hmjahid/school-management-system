@extends('layouts.dashboard')

@section('title', __('Students') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Students') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
        @can('create', App\Models\Student::class)
            <a href="{{ route('dashboard.students.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Add student') }}</a>
        @endcan
        <form method="get" class="flex flex-wrap gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search name, email, admission…') }}"
                class="min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">{{ __('Search') }}</button>
        </form>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Student') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Admission') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Class') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($students as $student)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $student->user?->name ?? __('N/A') }}</div>
                                <div class="text-gray-500">{{ $student->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $student->admission_number ?? $student->admission_no ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $student->class?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800">{{ $student->status ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                @can('view', $student)
                                    <a href="{{ route('dashboard.students.show', $student) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">{{ __('No students found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $students->links() }}</div>
        @endif
    </div>
@endsection
