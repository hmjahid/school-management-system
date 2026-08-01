@extends('layouts.dashboard')
@section('title', __('Committee Members') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Committee Members') }}</h1>
    <a href="{{ route('dashboard.committee.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('New member') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name, designation...') }}" class="min-w-[200px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Photo') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Name') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Designation') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Phone') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Order') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($members as $m)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $m->id }}</td>
                    <td class="px-4 py-3">
                        @if($m->photo_url)
                            <img src="{{ $m->photo_url }}" alt="{{ $m->name }}" class="h-10 w-10 rounded-full object-cover">
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                                {{ strtoupper(substr($m->name, 0, 1)) }}
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 font-medium">{{ $m->name }}</td>
                    <td class="px-4 py-3">{{ $m->designation }}</td>
                    <td class="px-4 py-3">{{ $m->phone ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $m->sort_order }}</td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $m->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $m->is_active ? __('Active') : __('Inactive') }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.committee.edit', $m) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                        <form method="post" action="{{ route('dashboard.committee.destroy', $m) }}" class="inline ml-2" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                            @csrf @method('delete')
                            <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('No committee members found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $members->links() }}</div>
@endsection
