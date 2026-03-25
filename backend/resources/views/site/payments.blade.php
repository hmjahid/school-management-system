@extends('layouts.app')

@section('title', ($content->title ?? __('Fee payments')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? __('Fee payment portal'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
    @include('site.partials.sections', ['content' => $content])

    <section class="mt-12">
        <h2 class="text-lg font-semibold text-gray-900">{{ __('Fee structure (active fees)') }}</h2>
        @if($feeRows->isEmpty())
            <p class="mt-4 text-sm text-gray-600">{{ __('Configure fee items in the school dashboard; they will appear here automatically.') }}</p>
        @else
            <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Name') }}</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Type') }}</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Amount') }}</th>
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
        <h2 class="text-lg font-semibold text-gray-900">{{ __('Online payment gateways') }}</h2>
        <p class="mt-2 text-sm text-gray-600">{{ __('Mobile banking and card gateways are configured by administrators. Initiation typically happens from the dashboard or mobile app using secure API tokens.') }}</p>
        @if($gateways->isEmpty())
            <p class="mt-4 text-sm text-gray-500">{{ __('No active gateways in the database.') }}</p>
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
                <h2 class="text-lg font-semibold text-gray-900">{{ __('Your payment history') }}</h2>
                <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Invoice') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Date') }}</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">{{ __('Paid') }}</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($feePayments as $p)
                                <tr>
                                    <td class="px-4 py-3 font-mono text-xs text-gray-800">{{ $p->invoice_number }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $p->payment_date?->format('M j, Y') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-right text-gray-900">{{ number_format((float) $p->paid_amount, 2) }}</td>
                                    <td class="px-4 py-3 text-gray-600">{{ $p->status }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="mt-3 text-xs text-gray-500">{{ __('Receipts: contact the finance office or use notifications from completed online transactions.') }}</p>
            </section>
        @elseif(auth()->user()->hasAnyRole(['parent', 'student']))
            <p class="mt-8 text-sm text-gray-600">{{ __('No fee payments on file for your linked students yet.') }}</p>
        @endif
    @else
        <p class="mt-8 text-sm text-gray-600">{{ __('Log in as a parent or student to see personal payment history when linked in the system.') }}</p>
        <a href="{{ route('login') }}" class="mt-2 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Log in') }} →</a>
    @endauth
        </div>
    </div>
@endsection
