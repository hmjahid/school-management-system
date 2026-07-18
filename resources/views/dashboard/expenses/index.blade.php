@extends('layouts.dashboard')

@section('title', __('Expenses') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Expenses')" :description="__('Track and categorize expenses; entries post to the ledger automatically.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Expenses')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.expenses.export')" variant="ghost" size="sm">{{ __('Export CSV') }}</x-button>
            <x-button :href="route('dashboard.expenses.create')">{{ __('New expense') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <form method="get" class="mb-6 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:grid-cols-4">
        <input type="date" name="from" value="{{ request('from') }}" class="admin-input" placeholder="{{ __('From') }}">
        <input type="date" name="to" value="{{ request('to') }}" class="admin-input" placeholder="{{ __('To') }}">
        <select name="category" class="admin-select">
            <option value="">{{ __('All categories') }}</option>
            @foreach($categories as $c)
                <option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary">{{ __('Filter') }}</x-button>
    </form>

    <x-card :padding="false">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3 text-sm">
            <span class="text-slate-600">{{ __('Total') }}</span>
            <span class="text-base font-bold text-slate-900">{{ number_format((float) $total, 2) }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Category') }}</th>
                        <th class="px-4 py-3">{{ __('Vendor') }}</th>
                        <th class="px-4 py-3">{{ __('Method') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Amount') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr>
                            <td class="px-4 py-3 text-slate-700">{{ $row->date?->format('Y-m-d') }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $row->category }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ $row->vendor ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-700">{{ strtoupper($row->payment_method ?? 'cash') }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">{{ number_format((float) $row->amount, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-button :href="route('dashboard.expenses.edit', $row)" variant="ghost" size="sm">{{ __('Edit') }}</x-button>
                                <form method="post" action="{{ route('dashboard.expenses.destroy', $row) }}" class="inline" onsubmit="return confirm('{{ __('Delete this expense?') }}')">
                                    @csrf @method('delete')
                                    <button class="text-xs font-semibold text-red-700 hover:underline" type="submit">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No expenses recorded yet.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($rows->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
        @endif
    </x-card>
@endsection