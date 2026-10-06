@extends('layouts.dashboard')

@section('title', __('Test payment sandbox') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Test payment sandbox')" :description="__('Run the full payment flow locally. No money moves and no external gateway is contacted.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Payment gateways'), 'url' => route('dashboard.payment-gateways.index')],
                ['label' => __('Test payment sandbox')],
            ]" />
        </x-slot:breadcrumbs>
        <x-slot:actions>
            <x-button :href="route('dashboard.payment-gateways.index')" variant="ghost">{{ __('Back to gateways') }}</x-button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 lg:grid-cols-2">
        <x-card :title="__('Payment')">
            <dl class="divide-y divide-slate-100 text-sm">
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-slate-500">{{ __('Gateway') }}</dt>
                    <dd class="font-medium text-slate-900">{{ $gateway->name }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-slate-500">{{ __('Invoice') }}</dt>
                    <dd class="font-mono text-xs text-slate-700">{{ $payment->invoice_number }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-slate-500">{{ __('Amount') }}</dt>
                    <dd class="font-medium text-slate-900">{{ number_format((float) $payment->total_amount, 2) }} {{ $gateway->currency }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-slate-500">{{ __('Status') }}</dt>
                    <dd>
                        @if($payment->payment_status === \App\Models\Payment::STATUS_COMPLETED)
                            <x-badge variant="success">{{ __('Completed') }}</x-badge>
                        @elseif($payment->payment_status === \App\Models\Payment::STATUS_FAILED)
                            <x-badge variant="danger">{{ __('Failed') }}</x-badge>
                        @elseif($payment->payment_status === \App\Models\Payment::STATUS_CANCELLED)
                            <x-badge variant="warning">{{ __('Cancelled') }}</x-badge>
                        @elseif($payment->payment_status === \App\Models\Payment::STATUS_PROCESSING)
                            <x-badge variant="info">{{ __('Processing') }}</x-badge>
                        @else
                            <x-badge>{{ __(ucfirst($payment->payment_status)) }}</x-badge>
                        @endif
                    </dd>
                </div>
                @if($payment->transaction_id)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <dt class="text-slate-500">{{ __('Transaction ID') }}</dt>
                        <dd class="font-mono text-xs text-slate-700">{{ $payment->transaction_id }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card :title="__('Simulate an outcome')">
            <p class="text-sm text-slate-600">{{ __('This is a free test payment. Choose an outcome below — the payment is updated exactly as a real gateway would update it, but nothing is charged.') }}</p>

            <div class="mt-4 flex flex-wrap gap-3">
                <form method="post" action="{{ route('payments.sandbox.simulate', ['payment' => $payment->id]) }}">
                    @csrf
                    <input type="hidden" name="simulate" value="success">
                    <x-button type="submit" variant="primary">{{ __('Simulate success') }}</x-button>
                </form>

                <form method="post" action="{{ route('payments.sandbox.simulate', ['payment' => $payment->id]) }}">
                    @csrf
                    <input type="hidden" name="simulate" value="failure">
                    <x-button type="submit" variant="secondary">{{ __('Simulate failure') }}</x-button>
                </form>

                <form method="post" action="{{ route('payments.sandbox.simulate', ['payment' => $payment->id]) }}">
                    @csrf
                    <input type="hidden" name="simulate" value="cancel">
                    <x-button type="submit" variant="ghost">{{ __('Simulate cancellation') }}</x-button>
                </form>
            </div>

            @if($payment->payment_status !== \App\Models\Payment::STATUS_PENDING)
                <p class="mt-4 text-xs text-slate-500">{{ __('Simulating another outcome on a finished payment is ignored — only a pending payment changes.') }}</p>
            @endif
        </x-card>
    </div>
@endsection
