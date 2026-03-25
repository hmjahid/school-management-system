@extends('layouts.app')

@section('title', site_ui('payment_status.page_title') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="mx-auto max-w-4xl px-4 py-10">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ site_ui('payment_status.page_title') }}</h1>
                <p class="mt-1 text-sm text-gray-600">{{ site_ui('payment_status.intro') }}</p>
            </div>
            <a href="{{ route('site.payments') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ site_ui('payment_status.back') }}</a>
        </div>

        <section class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            @if($payment->payment_status === \App\Models\Payment::STATUS_COMPLETED)
                <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900" role="status" aria-live="polite">
                    <div class="font-semibold">{{ site_ui('payment_status.success_title') }}</div>
                    <div class="mt-1 text-emerald-800">{{ site_ui('payment_status.success_body') }}</div>
                </div>
            @elseif(in_array($payment->payment_status, [\App\Models\Payment::STATUS_FAILED, \App\Models\Payment::STATUS_CANCELLED, \App\Models\Payment::STATUS_EXPIRED], true))
                <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900" role="status" aria-live="polite">
                    <div class="font-semibold">{{ site_ui('payment_status.failed_title') }}</div>
                    <div class="mt-1 text-red-800">{{ site_ui('payment_status.failed_body') }}</div>
                </div>
            @else
                <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status" aria-live="polite">
                    <div class="font-semibold">{{ site_ui('payment_status.pending_title') }}</div>
                    <div class="mt-1 text-amber-800">{{ site_ui('payment_status.pending_body') }}</div>
                </div>
            @endif

            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <dt class="text-gray-500">{{ site_ui('payment_status.invoice') }}</dt>
                <dd class="font-mono text-gray-900">{{ $payment->invoice_number }}</dd>
                <dt class="text-gray-500">{{ site_ui('payment_status.gateway') }}</dt>
                <dd class="text-gray-900">{{ $payment->method_label }}</dd>
                <dt class="text-gray-500">{{ site_ui('payment_status.status') }}</dt>
                <dd class="text-gray-900">{{ $payment->status_label }}</dd>
                <dt class="text-gray-500">{{ site_ui('payment_status.amount') }}</dt>
                <dd class="text-gray-900">{{ number_format((float) $payment->total_amount, 2) }}</dd>
                <dt class="text-gray-500">{{ site_ui('payment_status.transaction') }}</dt>
                <dd class="font-mono text-gray-900">{{ $payment->transaction_id ?: '—' }}</dd>
            </dl>

            @if($feePayment)
                <div class="mt-6 rounded-lg border border-gray-100 bg-gray-50 p-4 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <div class="text-gray-700">{{ str_replace(':inv', $feePayment->invoice_number, site_ui('payment_status.fee_invoice')) }}</div>
                            <div class="text-gray-600">{{ str_replace(':status', $feePayment->status, site_ui('payment_status.fee_payment_status')) }}</div>
                        </div>
                        @if($payment->payment_status === \App\Models\Payment::STATUS_COMPLETED && $feePayment->status === \App\Models\FeePayment::STATUS_PAID)
                            <a href="{{ route('site.payments.receipts.show', $feePayment) }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                {{ site_ui('payment_status.receipt') }}
                            </a>
                        @else
                            <span class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-semibold text-gray-700" aria-disabled="true">
                                {{ site_ui('payment_status.receipt') }}
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            <p class="mt-4 text-xs text-gray-500">{{ site_ui('payment_status.tip_refresh') }}</p>
        </section>
    </div>
@endsection

