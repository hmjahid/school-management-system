@extends('layouts.app')

@section('title', site_ui('fee_receipt.page_title') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ site_ui('fee_receipt.heading') }}</h1>
                    <p class="mt-1 text-sm text-gray-600">{{ site_ui('fee_receipt.invoice') }}: <span class="font-mono">{{ $p->invoice_number }}</span></p>
                </div>
                <div class="flex items-center gap-2 print:hidden">
                    <a href="{{ route('site.payments') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ site_ui('fee_receipt.back') }}</a>
                    <button onclick="window.print()" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ site_ui('fee_receipt.print') }}</button>
                </div>
            </div>

            <div class="mt-8 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ site_ui('fee_receipt.billed_to') }}</h2>
                        <p class="mt-2 text-sm text-gray-900">
                            <span class="font-medium">{{ $p->student?->user?->name ?? site_ui('fee_receipt.student') }}</span><br>
                            @if($p->student?->user?->email)
                                {{ $p->student->user->email }}<br>
                            @endif
                            @if($p->student?->user?->phone)
                                {{ $p->student->user->phone }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">{{ site_ui('fee_receipt.payment_details') }}</h2>
                        <dl class="mt-2 space-y-1 text-sm">
                            <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ site_ui('fee_receipt.date') }}</dt><dd class="text-gray-900">{{ $p->payment_date?->format('Y-m-d') ?? '—' }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ site_ui('fee_receipt.method') }}</dt><dd class="text-gray-900">{{ $p->payment_method_label }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ site_ui('fee_receipt.status') }}</dt><dd class="text-gray-900">{{ ucfirst($p->status) }}</dd></div>
                            @if($p->transaction_id)
                                <div class="flex justify-between gap-4"><dt class="text-gray-600">{{ site_ui('fee_receipt.transaction') }}</dt><dd class="font-mono text-xs text-gray-900">{{ $p->transaction_id }}</dd></div>
                            @endif
                        </dl>
                    </div>
                </div>

                <div class="mt-8 overflow-hidden rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('fee_receipt.fee_item') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ site_ui('fee_receipt.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr>
                                <td class="px-4 py-3 text-gray-900">
                                    <div class="font-medium">{{ $p->fee?->name ?? site_ui('fee_receipt.fee') }}</div>
                                    @if($p->month || $p->year)
                                        <div class="text-xs text-gray-500">{{ site_ui('fee_receipt.period') }}: {{ trim(($p->month ?? '') . ' ' . ($p->year ?? '')) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-gray-900">{{ number_format((float) $p->amount, 2) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-white">
                            <tr>
                                <td class="px-4 py-3 text-right font-medium text-gray-700">{{ site_ui('fee_receipt.discount') }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">-{{ number_format((float) $p->discount_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-right font-medium text-gray-700">{{ site_ui('fee_receipt.fine') }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">{{ number_format((float) $p->fine_amount, 2) }}</td>
                            </tr>
                            <tr class="border-t border-gray-200">
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ site_ui('fee_receipt.paid') }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-900">{{ number_format((float) $p->paid_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="px-4 py-3 text-right font-medium text-gray-700">{{ site_ui('fee_receipt.balance') }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">{{ number_format((float) $p->balance, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($p->notes)
                    <div class="mt-6 text-sm text-gray-700">
                        <div class="font-semibold text-gray-900">{{ site_ui('fee_receipt.notes') }}</div>
                        <div class="mt-1 whitespace-pre-line">{{ $p->notes }}</div>
                    </div>
                @endif

                <div class="mt-8 text-xs text-gray-500">
                    {{ str_replace(':name', $siteSettings->school_name ?? config('app.name'), site_ui('fee_receipt.generated_by')) }}
                </div>
            </div>
        </div>
    </div>
@endsection
