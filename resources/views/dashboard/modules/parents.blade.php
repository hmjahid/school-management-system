@extends('layouts.dashboard')

@section('title', __('Parents & guardians') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Parents & guardians') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
        @can('create', App\Models\Guardian::class)
            <a href="{{ route('dashboard.parents.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Add guardian') }}</a>
        @endcan
        <form method="get" class="flex flex-wrap gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}"
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
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Guardian') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Phone') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Students') }}</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($guardians as $guardian)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $guardian->user?->name ?? __('N/A') }}</div>
                                <div class="text-gray-500">{{ $guardian->user?->email }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $guardian->phone ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $guardian->students->map(fn ($s) => $s->user?->name)->filter()->implode(', ') ?: '—' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                @can('view', $guardian)
                                    <a href="{{ route('dashboard.parents.show', $guardian) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-8 text-center text-gray-500">{{ __('No guardians found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($guardians->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $guardians->links() }}</div>
        @endif
    </div>
@endsection
