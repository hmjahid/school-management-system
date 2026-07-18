@extends('layouts.dashboard')

@section('title', __('Generate payslips') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Generate payslips')" :description="$month . '/' . $year">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payroll')],
                ['label' => __('Generate')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
        <select name="month" class="admin-select">
            @foreach(range(1, 12) as $m)
                <option value="{{ $m }}" @selected($month == $m)>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
            @endforeach
        </select>
        <input type="number" name="year" min="2020" max="2099" value="{{ $year }}" class="admin-input">
        <x-button type="submit" variant="secondary">{{ __('Preview') }}</x-button>
    </form>

    <form method="post" action="{{ route('dashboard.payroll.generate.store') }}">
        @csrf
        <input type="hidden" name="month" value="{{ $month }}">
        <input type="hidden" name="year" value="{{ $year }}">

        <x-card :padding="false">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3"><input type="checkbox" checked onchange="document.querySelectorAll('.pay-check').forEach(c => c.checked = this.checked)"></th>
                        <th class="px-4 py-3">{{ __('Teacher') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Basic') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Allowances') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Leave days') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Deductions') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Net') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($preview as $p)
                        <tr>
                            <td class="px-4 py-3"><input type="checkbox" name="teacher_ids[]" value="{{ $p['teacher']->id }}" class="pay-check" checked></td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $p['teacher']->user?->name }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($p['basic'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($p['allowances'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ $p['leave_days'] }}</td>
                            <td class="px-4 py-3 text-right font-mono">{{ number_format($p['deductions'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-mono font-semibold">{{ number_format($p['net'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No active structures for this month.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($preview->isNotEmpty())
                <div class="flex justify-end border-t border-slate-200 px-5 py-4">
                    <x-button type="submit">{{ __('Generate payslips') }}</x-button>
                </div>
            @endif
        </x-card>
    </form>
@endsection