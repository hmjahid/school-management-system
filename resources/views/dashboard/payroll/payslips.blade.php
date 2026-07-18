@extends('layouts.dashboard')

@section('title', __('Payslips') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Payslips')" :description="$month . '/' . $year">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payroll')],
                ['label' => __('Payslips')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.payroll.generate')" variant="ghost" size="sm">{{ __('Generate') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
        <select name="month" class="admin-select">
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
            @endforeach
        </select>
        <input type="number" name="year" min="2020" max="2099" value="{{ $year }}" class="admin-input">
        <x-button type="submit" variant="secondary">{{ __('Filter') }}</x-button>
    </form>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Teacher') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Basic') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Allowances') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Deductions') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Net') }}</th>
                    <th class="px-4 py-3">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $p)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $p->teacher?->user?->name }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $p->basic, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $p->total_allowances, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono">{{ number_format((float) $p->total_deductions, 2) }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold">{{ number_format((float) $p->net_salary, 2) }}</td>
                        <td class="px-4 py-3">
                            @if($p->status === 'paid')
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-800">{{ __('Paid') }}</span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-700">{{ __('Draft') }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-button :href="route('dashboard.payroll.payslips.show', $p)" variant="ghost" size="sm">{{ __('View') }}</x-button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No payslips for this period.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection