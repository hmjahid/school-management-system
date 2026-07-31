@extends('layouts.dashboard')
@section('title', __('Fee payments') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Fee payments') }}</h1>
    <a href="{{ route('dashboard.settings.general', ['tab' => 'payment']) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Payment Configuration') }}</a>
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search invoice or student...') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <select name="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
        <option value="">{{ __('All statuses') }}</option>
        @foreach(['pending','paid','partial','cancelled','refunded'] as $s)
            <option value="{{ $s }}" @selected(request('status') === $s)>{{ __(ucfirst($s)) }}</option>
        @endforeach
    </select>
    <select name="payment_method" class="rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        <option value="">{{ __('All methods') }}</option>
        <option value="cash" @selected(request('payment_method') === 'cash')>{{ __('Cash') }}</option>
        <option value="bank_transfer" @selected(request('payment_method') === 'bank_transfer')>{{ __('Bank Transfer') }}</option>
        <option value="check" @selected(request('payment_method') === 'check')>{{ __('Check') }}</option>
        <option value="online_payment" @selected(request('payment_method') === 'online_payment')>{{ __('Online Payment') }}</option>
        <option value="mobile_banking" @selected(request('payment_method') === 'mobile_banking')>{{ __('Mobile Banking') }}</option>
        <option value="other" @selected(request('payment_method') === 'other')>{{ __('Other') }}</option>
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Invoice') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Student') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Fee') }}</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">{{ __('Amount') }}</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">{{ __('Paid') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Status') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Date') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @forelse($payments as $p)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-mono text-xs">{{ $p->invoice_number }}</td>
                    <td class="px-4 py-3">{{ $p->student?->user?->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">{{ $p->fee?->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($p->amount, 2) }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($p->paid_amount, 2) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $colors = ['pending'=>'yellow','paid'=>'green','partial'=>'orange','cancelled'=>'red','refunded'=>'purple'];
                            $color = $colors[$p->status] ?? 'gray';
                        @endphp
                        <span class="rounded-full bg-{{ $color }}-100 px-2.5 py-0.5 text-xs font-medium text-{{ $color }}-700">{{ __(ucfirst($p->status)) }}</span>
                    </td>
                    <td class="px-4 py-3">{{ $p->payment_date?->format('d M Y') ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.fee-payments.show', $p) }}" class="text-blue-600 hover:text-blue-800">{{ __('View') }}</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('No payments found.') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $payments->links() }}</div>
@endsection
