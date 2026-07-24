@extends('layouts.dashboard')
@section('title', __('Payment detail') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Payment detail') }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.fee-payments.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200">{{ __('Back') }}</a>
        @if($payment->status === 'pending')
            <form method="post" action="{{ route('dashboard.fee-payments.approve', $payment) }}" onsubmit="return confirm('{{ __('Approve this payment?') }}')">
                @csrf
                <button type="submit" class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">{{ __('Approve') }}</button>
            </form>
            <form method="post" action="{{ route('dashboard.fee-payments.cancel', $payment) }}" onsubmit="return confirm('{{ __('Cancel this payment?') }}')">
                @csrf
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Cancel') }}</button>
            </form>
        @endif
    </div>
</div>
<div class="grid gap-6 sm:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold uppercase text-gray-500">{{ __('Payment info') }}</h2>
        <dl class="space-y-4 text-sm">
            <div><dt class="text-gray-500">{{ __('Invoice number') }}</dt><dd class="font-mono font-medium">{{ $payment->invoice_number }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Student') }}</dt><dd class="font-medium">{{ $payment->student?->user?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Fee') }}</dt><dd>{{ $payment->fee?->name ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Amount') }}</dt><dd class="font-medium">{{ number_format($payment->amount, 2) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Discount') }}</dt><dd>{{ number_format($payment->discount_amount, 2) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Fine') }}</dt><dd>{{ number_format($payment->fine_amount, 2) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Paid') }}</dt><dd class="font-medium text-green-700">{{ number_format($payment->paid_amount, 2) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Balance') }}</dt><dd class="font-medium text-red-700">{{ number_format($payment->balance, 2) }}</dd></div>
        </dl>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        <h2 class="mb-4 text-sm font-semibold uppercase text-gray-500">{{ __('Details') }}</h2>
        <dl class="space-y-4 text-sm">
            <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd><span class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700">{{ __(ucfirst($payment->status)) }}</span></dd></div>
            <div><dt class="text-gray-500">{{ __('Payment method') }}</dt><dd>{{ __(ucfirst(str_replace('_',' ', $payment->payment_method))) }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Payment date') }}</dt><dd>{{ $payment->payment_date?->format('d M Y') ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Month/Year') }}</dt><dd>{{ $payment->month ? $payment->month . '/' . $payment->year : '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Transaction ID') }}</dt><dd class="font-mono">{{ $payment->transaction_id ?? '-' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Created by') }}</dt><dd>{{ $payment->creator?->name ?? 'N/A' }}</dd></div>
            <div><dt class="text-gray-500">{{ __('Notes') }}</dt><dd>{{ $payment->notes ?? '-' }}</dd></div>
        </dl>
    </div>
</div>
@endsection
