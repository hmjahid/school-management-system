@extends('layouts.dashboard')

@section('title', __('Bank Reconciliation') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Bank Reconciliation')" :description="__('Reconcile bank ledger entries against your bank statement.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Ledger'), 'url' => route('dashboard.ledger.index')],
                ['label' => __('Bank Reconciliation')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        <form method="get" action="{{ route('dashboard.bank-reconciliation.index') }}" class="grid gap-4 md:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Bank account') }}</label>
                <select name="account_id" class="admin-select">
                    <option value="">{{ __('All bank accounts') }}</option>
                    @foreach($bankAccounts as $acc)
                        <option value="{{ $acc->id }}" @selected($accountId === $acc->id)>{{ $acc->name_en ?? $acc->name_bn }} ({{ $acc->code }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('From') }}</label>
                <input type="date" name="from" value="{{ $from }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('To') }}</label>
                <input type="date" name="to" value="{{ $to }}" class="admin-input">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">{{ __('Statement ending balance') }}</label>
                <input type="number" step="0.01" name="statement_balance" value="{{ $statementBalance }}" class="admin-input" placeholder="0.00">
            </div>
            <div class="flex items-end gap-2 md:col-span-4">
                <x-button type="submit">{{ __('Filter') }}</x-button>
                <button type="submit" formaction="{{ route('dashboard.bank-reconciliation.reconcile') }}" class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700">
                    {{ __('Reconcile') }}
                </button>
                <a href="{{ route('dashboard.bank-reconciliation.index') }}" class="text-sm text-slate-500 hover:underline">{{ __('Reset') }}</a>
            </div>
        </form>
    </x-card>

    @if($reconciled)
        <x-card class="mt-4">
            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Book balance (ledger)') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($bookBalance, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Statement ending balance') }}</div>
                    <div class="mt-1 text-2xl font-semibold text-slate-900">{{ number_format($statementBalance, 2) }}</div>
                </div>
                <div>
                    <div class="text-xs uppercase tracking-wide text-slate-500">{{ __('Difference') }}</div>
                    <div class="mt-1 text-2xl font-semibold @if(abs($difference) < 0.01) text-emerald-600 @else text-red-600 @endif">
                        {{ number_format($difference, 2) }}
                    </div>
                </div>
            </div>
            @if(abs($difference) < 0.01)
                <p class="mt-3 text-sm font-medium text-emerald-600">{{ __('Reconciled: ledger matches the bank statement.') }}</p>
            @else
                <p class="mt-3 text-sm font-medium text-red-600">{{ __('Out of balance: investigate missing or unrecorded entries.') }}</p>
            @endif
        </x-card>
    @endif

    <x-card class="mt-4" :padding="false">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 px-4 py-3 text-sm">
            <div class="font-semibold text-slate-700">{{ __('Bank entries') }}</div>
            <div class="flex gap-4 text-xs text-slate-500">
                <span>{{ __('Total debit') }}: <span class="font-semibold text-slate-700">{{ number_format($totalDebit, 2) }}</span></span>
                <span>{{ __('Total credit') }}: <span class="font-semibold text-slate-700">{{ number_format($totalCredit, 2) }}</span></span>
                <span>{{ __('Book balance') }}: <span class="font-semibold text-slate-700">{{ number_format($bookBalance, 2) }}</span></span>
            </div>
        </div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Date') }}</th>
                    <th class="px-4 py-3">{{ __('Account') }}</th>
                    <th class="px-4 py-3">{{ __('Reference') }}</th>
                    <th class="px-4 py-3">{{ __('Note') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Debit') }}</th>
                    <th class="px-4 py-3 text-right">{{ __('Credit') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($entries as $e)
                    <tr>
                        <td class="px-4 py-3 text-slate-700">{{ $e->date?->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $e->account?->name_en ?? $e->account?->name_bn }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ class_basename($e->reference_type) ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $e->note ?: '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ $e->debit ? number_format($e->debit, 2) : '' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ $e->credit ? number_format($e->credit, 2) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No bank entries found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($entries->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $entries->links() }}</div>
        @endif
    </x-card>
@endsection
