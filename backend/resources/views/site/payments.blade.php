@extends('layouts.app')

@section('title', ($content->title ?? site_ui('payments_page.document_title')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? site_ui('payments_page.hero_fallback'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('site.partials.sections', ['content' => $content])

    @auth
        @if(($students ?? collect())->isNotEmpty())
            <section class="mt-10 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('payments.pay_section_title') }}</h2>
                <p class="mt-2 text-sm text-gray-600">{{ site_ui('payments.pay_section_intro') }}</p>

                <form method="post" action="{{ route('site.payments.initiate') }}" class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('payments.label_student') }}</label>
                        <select name="student_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                            @foreach($students as $s)
                                <option value="{{ $s->id }}">{{ $s->first_name }} {{ $s->last_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('payments.label_fee_item') }}</label>
                        <select name="fee_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                            @foreach($feeRows as $fee)
                                <option value="{{ $fee->id }}">{{ $fee->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('payments.label_gateway') }}</label>
                        <select name="gateway" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                            @foreach($gateways as $g)
                                <option value="{{ $g->code }}">{{ $g->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('payments.label_amount') }}</label>
                        <input name="amount" type="number" min="1" step="0.01" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4">
                        <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ site_ui('payments.proceed') }}</button>
                    </div>
                </form>
            </section>
        @endif
    @endauth

    <section class="mt-12">
        <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('payments.fee_table_title') }}</h2>
        @if($feeRows->isEmpty())
            <p class="mt-4 text-sm text-gray-600">{{ site_ui('payments.fee_table_empty') }}</p>
        @else
            <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('payments.col_name') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('payments.col_type') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ site_ui('payments.col_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($feeRows as $fee)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $fee->name }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $fee->fee_type ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-gray-900">{{ $fee->formatted_amount ?? number_format((float) $fee->amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="mt-12">
        <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('payments.gateways_title') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ site_ui('payments.gateways_intro') }}</p>
        @if($gateways->isEmpty())
            <p class="mt-4 text-sm text-gray-500">{{ site_ui('payments.gateways_none') }}</p>
        @else
            <ul class="mt-4 grid gap-3 sm:grid-cols-2">
                @foreach ($gateways as $g)
                    <li class="rounded-lg border border-gray-200 bg-white px-4 py-3 shadow-sm">
                        <span class="font-medium text-gray-900">{{ $g->name }}</span>
                        @if($g->description)
                            <p class="mt-1 text-xs text-gray-600">{{ \Illuminate\Support\Str::limit($g->description, 120) }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @auth
        @if($feePayments->isNotEmpty())
            <section class="mt-12">
                <h2 class="text-lg font-semibold text-gray-900">{{ site_ui('payments.history_title') }}</h2>
                <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('payments.col_invoice') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('payments.col_date') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ site_ui('payments.col_paid') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ site_ui('payments.col_status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($feePayments as $p)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-800">{{ $p->invoice_number }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $p->payment_date?->format('M j, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ number_format((float) $p->paid_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-gray-600">
                                        <div class="flex items-center justify-between gap-3">
                                            <span>{{ $p->status }}</span>
                                            <a href="{{ route('site.payments.receipts.show', $p) }}" class="text-xs font-medium text-blue-600 hover:underline">{{ site_ui('payments.receipt') }}</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">{{ site_ui('payments.history_footer') }}</p>
            </section>
        @elseif(auth()->user()->hasAnyRole(['parent', 'student']))
            <p class="mt-8 text-sm text-gray-600">{{ site_ui('payments.no_payments_linked') }}</p>
        @endif
    @else
        <p class="mt-8 text-sm text-gray-600">{{ site_ui('payments.login_prompt') }}</p>
        <a href="{{ route('login') }}" class="mt-2 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ site_ui('payments.login_link') }} →</a>
    @endauth
        </div>
    </div>
@endsection
