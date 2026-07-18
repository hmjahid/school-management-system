@extends('layouts.dashboard')

@section('title', __('Payslip') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Payslip') . ' — ' . $payslip->teacher?->user?->name" :description="$payslip->monthName() . ' ' . $payslip->year">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payroll'), 'url' => route('dashboard.payroll.payslips')],
                ['label' => $payslip->teacher?->user?->name],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            @if($payslip->status === 'draft' && auth()->user()->can('manage_teacher_salaries'))
                <form method="post" action="{{ route('dashboard.payroll.payslips.markPaid', $payslip) }}" onsubmit="return confirm('{{ __('Mark as paid and post to ledger?') }}')">
                    @csrf
                    <x-button type="submit">{{ __('Mark paid') }}</x-button>
                </form>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <x-card class="lg:col-span-2">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Earnings') }}</h2>
            <table class="mt-2 w-full text-sm">
                <tr class="border-b border-slate-100"><td class="py-1.5 text-slate-700">{{ __('Basic') }}</td><td class="py-1.5 text-right font-mono">{{ number_format((float) $payslip->basic, 2) }}</td></tr>
                @foreach($payslip->details['allowances'] ?? [] as $a)
                    <tr class="border-b border-slate-100"><td class="py-1.5 text-slate-700">{{ $a['name'] }}</td><td class="py-1.5 text-right font-mono">{{ number_format((float) $a['amount'], 2) }}</td></tr>
                @endforeach
                <tr class="font-semibold"><td class="py-2 text-slate-900">{{ __('Total earnings') }}</td><td class="py-2 text-right font-mono">{{ number_format((float) $payslip->basic + (float) $payslip->total_allowances, 2) }}</td></tr>
            </table>

            <h2 class="mt-6 text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('Deductions') }}</h2>
            <table class="mt-2 w-full text-sm">
                @foreach($payslip->details['deductions'] ?? [] as $d)
                    <tr class="border-b border-slate-100"><td class="py-1.5 text-slate-700">{{ $d['name'] }}</td><td class="py-1.5 text-right font-mono">{{ number_format((float) $d['amount'], 2) }}</td></tr>
                @endforeach
                @if(($payslip->details['leave_days'] ?? 0) > 0)
                    <tr class="border-b border-slate-100"><td class="py-1.5 text-slate-700">{{ __('Leave deduction') }} ({{ $payslip->details['leave_days'] }} days)</td><td class="py-1.5 text-right font-mono">{{ number_format((float) ($payslip->details['leave_deduction'] ?? 0), 2) }}</td></tr>
                @endif
                <tr class="font-semibold"><td class="py-2 text-slate-900">{{ __('Total deductions') }}</td><td class="py-2 text-right font-mono">{{ number_format((float) $payslip->total_deductions, 2) }}</td></tr>
            </table>
        </x-card>

        <x-card>
            <div class="text-center">
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ __('Net salary') }}</p>
                <p class="mt-1 text-4xl font-bold text-emerald-700">{{ number_format((float) $payslip->net_salary, 2) }}</p>
                <p class="mt-3">
                    @if($payslip->status === 'paid')
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">{{ __('Paid on') }} {{ $payslip->paid_at?->format('M j, Y') }}</span>
                    @else
                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-700">{{ __('Draft') }}</span>
                    @endif
                </p>
            </div>
        </x-card>
    </div>
@endsection