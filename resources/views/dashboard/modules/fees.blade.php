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
            <select name="fee_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                <option value="">{{ __('Any type') }}</option>
                <option value="tuition" @selected(request('fee_type') === 'tuition')>{{ __('Tuition') }}</option>
                <option value="admission" @selected(request('fee_type') === 'admission')>{{ __('Admission') }}</option>
                <option value="exam" @selected(request('fee_type') === 'exam')>{{ __('Exam') }}</option>
                <option value="transport" @selected(request('fee_type') === 'transport')>{{ __('Transport') }}</option>
                <option value="library" @selected(request('fee_type') === 'library')>{{ __('Library') }}</option>
                <option value="uniform" @selected(request('fee_type') === 'uniform')>{{ __('Uniform') }}</option>
                <option value="other" @selected(request('fee_type') === 'other')>{{ __('Other') }}</option>
            </select>
            <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Filter') }}</button>
        </form>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($fees as $fee)
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md">
                <div class="mb-3 flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $fee->name }}</h3>
                        @if($fee->code)
                            <p class="mt-0.5 font-mono text-xs text-gray-500">{{ $fee->code }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $fee->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ ucfirst($fee->status ?? 'active') }}</span>
                </div>
                <div class="mb-3 space-y-1 text-sm text-gray-600">
                    <div class="flex justify-between"><span>{{ __('Amount') }}</span><span class="font-medium text-gray-900">{{ number_format((float) ($fee->amount ?? 0), 2) }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Type') }}</span><span class="font-medium text-gray-900">{{ ucfirst($fee->fee_type ?? 'other') }}</span></div>
                    <div class="flex justify-between"><span>{{ __('Class') }}</span><span class="font-medium text-gray-900">{{ $fee->schoolClass?->name ?? '—' }}</span></div>
                </div>
                @if ($canManageFees)
                    <a href="{{ route('dashboard.fees.edit', $fee) }}" class="block w-full rounded-lg border border-blue-200 bg-blue-50 py-2 text-center text-sm font-medium text-blue-700 hover:bg-blue-100">{{ __('Edit') }}</a>
                @endif
            </div>
        @empty
            <div class="col-span-full py-12 text-center text-gray-500">{{ __('No fee definitions found.') }}</div>
        @endforelse
    </div>
    @if ($fees->hasPages())
        <div class="mt-4">{{ $fees->links() }}</div>
    @endif
@endsection
