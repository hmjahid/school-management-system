@extends('layouts.dashboard')

@section('title', __('Ledger') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('General ledger')" :description="__('Double-entry postings from fees, expenses, and manual journal entries.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Ledger')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.ledger.cashbook')" variant="ghost" size="sm">{{ __('Cashbook') }}</x-button>
            <x-button :href="route('dashboard.ledger.bankbook')" variant="ghost" size="sm">{{ __('Bankbook') }}</x-button>
            <x-button :href="route('dashboard.ledger.journal')">{{ __('New journal entry') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <input type="date" name="from" value="{{ request('from') }}" class="admin-input">
        <input type="date" name="to" value="{{ request('to') }}" class="admin-input">
        <select name="account_id" class="admin-select">
            <option value="">{{ __('All accounts') }}</option>
            @foreach($accounts as $a)
                <option value="{{ $a->id }}" @selected(request('account_id') == $a->id)>{{ $a->code }} — {{ $a->name_en }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">{{ __('Filter') }}</x-button>
    </form>

    <x-card :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Account') }}</th>
                        <th class="px-4 py-3">{{ __('Reference') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Debit') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Credit') }}</th>
                        <th class="px-4 py-3">{{ __('Note') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $r)
                        <tr>
                            <td class="px-4 py-2 text-slate-700">{{ $r->date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-2 font-medium text-slate-900">
                                <span class="font-mono text-xs text-slate-500">{{ $r->account?->code }}</span>
                                {{ $r->account?->name_en }}
                            </td>
                            <td class="px-4 py-2 text-slate-700">{{ $r->reference_type ? class_basename($r->reference_type).' #'.$r->reference_id : '—' }}</td>
                            <td class="px-4 py-2 text-right text-slate-900">{{ $r->debit > 0 ? number_format((float) $r->debit, 2) : '' }}</td>
                            <td class="px-4 py-2 text-right text-slate-900">{{ $r->credit > 0 ? number_format((float) $r->credit, 2) : '' }}</td>
                            <td class="px-4 py-2 text-xs text-slate-500">{{ $r->note ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No ledger entries yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection