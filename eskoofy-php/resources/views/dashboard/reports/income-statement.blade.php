@extends('layouts.dashboard')

@section('title', __('Income statement') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Income statement')" :description="$from . ' — ' . $to">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Reports')],
                ['label' => __('Income statement')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-3">
        <input type="date" name="from" value="{{ $from }}" class="admin-input">
        <input type="date" name="to" value="{{ $to }}" class="admin-input">
        <x-button type="submit" variant="secondary">{{ __('Refresh') }}</x-button>
    </form>

    <x-card>
        <div class="grid gap-6 md:grid-cols-2">
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-emerald-700">{{ __('Income') }}</h2>
                <table class="mt-2 w-full text-sm">
                    @foreach($incomeRows as $r)
                        <tr class="border-b border-slate-100">
                            <td class="py-1.5 text-slate-700">{{ $r['account']->name_en }}</td>
                            <td class="py-1.5 text-right font-mono">{{ number_format((float) $r['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold">
                        <td class="py-2 text-slate-900">{{ __('Total income') }}</td>
                        <td class="py-2 text-right font-mono text-emerald-700">{{ number_format((float) $totalIncome, 2) }}</td>
                    </tr>
                </table>
            </div>
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-red-700">{{ __('Expenses') }}</h2>
                <table class="mt-2 w-full text-sm">
                    @foreach($expenseRows as $r)
                        <tr class="border-b border-slate-100">
                            <td class="py-1.5 text-slate-700">{{ $r['account']->name_en }}</td>
                            <td class="py-1.5 text-right font-mono">{{ number_format((float) $r['amount'], 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold">
                        <td class="py-2 text-slate-900">{{ __('Total expenses') }}</td>
                        <td class="py-2 text-right font-mono text-red-700">{{ number_format((float) $totalExpense, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="mt-6 border-t border-slate-200 pt-4 text-right">
            <span class="text-sm font-semibold uppercase tracking-wide text-slate-600">{{ __('Net income') }}</span>
            <span class="ml-3 text-2xl font-bold {{ $net >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ number_format((float) $net, 2) }}</span>
        </div>
    </x-card>
@endsection