@extends('layouts.dashboard')

@section('title', __('Fees') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $u = auth()->user();
        $canManageFees = $u && ($u->hasAnyRole(['admin', 'accountant']) || $u->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']));
    @endphp
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Fees') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
        @if ($canManageFees)
            <a href="{{ route('dashboard.fees.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Add fee') }}</a>
        @endif
        <form method="get" class="flex flex-wrap gap-2">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search…') }}"
                class="min-w-[200px] rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">{{ __('Any status') }}</option>
                <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
            </select>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Filter') }}</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Name') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Code') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Amount') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Class') }}</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                        @if ($canManageFees)
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Actions') }}</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($fees as $fee)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $fee->name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $fee->code ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ number_format((float) ($fee->amount ?? 0), 2) }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $fee->schoolClass?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $fee->status ?? '—' }}</td>
                            @if ($canManageFees)
                                <td class="px-4 py-3 text-right text-sm">
                                    <a href="{{ route('dashboard.fees.edit', $fee) }}" class="font-medium text-blue-600 hover:text-blue-800">{{ __('Edit') }}</a>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canManageFees ? 6 : 5 }}" class="px-4 py-8 text-center text-gray-500">{{ __('No fee definitions found.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($fees->hasPages())
            <div class="border-t border-gray-200 px-4 py-3">{{ $fees->links() }}</div>
        @endif
    </div>
@endsection
