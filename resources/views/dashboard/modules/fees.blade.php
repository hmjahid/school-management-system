@extends('layouts.dashboard')

@section('title', __('Fees') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    @php
        $u = auth()->user();
        $canManageFees = $u && ($u->hasAnyRole(['admin', 'accountant']) || $u->hasAnyPermission(['collect_fees', 'view_financial_reports', 'manage_fee_categories', 'manage_fee_types']));
    @endphp

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">{{ __('Fees') }}</h1>
        <div class="flex flex-wrap items-center gap-2">
            @if ($canManageFees)
                <a href="{{ route('dashboard.fees.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Add fee') }}</a>
            @endif
        </div>
    </div>

    <form method="get" class="mb-6 flex flex-wrap gap-3">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name or code...') }}"
            class="min-w-[200px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">{{ __('All statuses') }}</option>
            <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
        </select>
        <select name="fee_type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            <option value="">{{ __('All types') }}</option>
            <option value="tuition" @selected(request('fee_type') === 'tuition')>{{ __('Tuition') }}</option>
            <option value="admission" @selected(request('fee_type') === 'admission')>{{ __('Admission') }}</option>
            <option value="exam" @selected(request('fee_type') === 'exam')>{{ __('Exam') }}</option>
            <option value="transport" @selected(request('fee_type') === 'transport')>{{ __('Transport') }}</option>
            <option value="library" @selected(request('fee_type') === 'library')>{{ __('Library') }}</option>
            <option value="uniform" @selected(request('fee_type') === 'uniform')>{{ __('Uniform') }}</option>
            <option value="other" @selected(request('fee_type') === 'other')>{{ __('Other') }}</option>
        </select>
        <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
    </form>

    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Name') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Code') }}</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">{{ __('Amount') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Type') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Class') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Frequency') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($fees as $fee)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $fee->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $fee->name }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $fee->code ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">{{ number_format((float) ($fee->amount ?? 0), 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-700">{{ __(ucfirst($fee->fee_type ?? 'other')) }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $fee->schoolClass?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ __(ucfirst($fee->frequency ?? 'one_time')) }}</td>
                        <td class="px-4 py-3">
                            @php
                                $statusColor = ($fee->status ?? 'active') === 'active' ? 'green' : 'gray';
                            @endphp
                            <span class="rounded-full bg-{{ $statusColor }}-100 px-2.5 py-0.5 text-xs font-medium text-{{ $statusColor }}-700">{{ __(ucfirst($fee->status ?? 'active')) }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($canManageFees)
                                <a href="{{ route('dashboard.fees.edit', $fee) }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Edit') }}</a>
                                <form method="post" action="{{ route('dashboard.fees.destroy', $fee) }}" class="inline ml-2" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                    @csrf @method('delete')
                                    <button type="submit" class="text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">{{ __('No fee definitions found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($fees->hasPages())
        <div class="mt-4">{{ $fees->links() }}</div>
    @endif
@endsection
