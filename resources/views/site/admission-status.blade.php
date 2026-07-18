@extends('layouts.app')

@section('title', site_ui('admission_status.page_title') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => site_ui('admission_status.hero_title'),
            'subtitle' => site_ui('admission_status.hero_subtitle'),
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

    <form method="get" action="{{ route('admissions.status') }}" class="flex max-w-xl flex-wrap gap-3">
        <label class="sr-only" for="application_number">{{ site_ui('admission_status.application_number') }}</label>
        <input type="text" id="application_number" name="application_number" value="{{ $applicationNumber ?? request('application_number') }}"
            placeholder="{{ site_ui('admission_status.placeholder') }}"
            class="min-w-[200px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        <button type="submit" class="rounded-md bg-orange-500 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-orange-600">{{ site_ui('admission_status.look_up') }}</button>
    </form>

    @if($applicationNumber)
        <div class="mt-10 rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-md">
            @if($admission)
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="text-xs uppercase tracking-wide text-gray-500">{{ site_ui('admission_status.application_number') }}</div>
                        <div class="font-mono text-lg text-gray-900">{{ $admission->application_number }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('admissions.receipt', $admission) }}" target="_blank" class="inline-flex items-center rounded-md bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">{{ __('Download receipt') }}</a>
                        @if($admission->payment_status === 'verified' && $admission->status === 'approved')
                            <a href="{{ route('admissions.approval-letter', $admission) }}" target="_blank" class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">{{ __('Download approval letter') }}</a>
                        @endif
                    </div>
                </div>

                <dl class="mt-6 grid gap-3 text-sm sm:grid-cols-2">
                    <dt class="font-medium text-gray-500">{{ site_ui('admission_status.applicant') }}</dt>
                    <dd class="text-gray-900">{{ $admission->full_name }}</dd>
                    <dt class="font-medium text-gray-500">{{ site_ui('admission_status.status') }}</dt>
                    <dd><span class="rounded-full px-2 py-1 text-xs font-semibold {{ $admission->status_badge }}">{{ $admission->status_label }}</span></dd>
                    <dt class="font-medium text-gray-500">{{ __('Admission fee') }}</dt>
                    <dd class="text-gray-900">{{ number_format((float) $admission->admission_fee, 2) }}</dd>
                    <dt class="font-medium text-gray-500">{{ __('Payment status') }}</dt>
                    <dd>
                        @php
                            $pClass = match($admission->payment_status) {
                                'verified' => 'bg-emerald-100 text-emerald-800',
                                'submitted' => 'bg-blue-100 text-blue-800',
                                'rejected' => 'bg-red-100 text-red-800',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <span class="rounded-full px-2 py-1 text-xs font-semibold {{ $pClass }}">{{ ucfirst($admission->payment_status) }}</span>
                    </dd>
                    <dt class="font-medium text-gray-500">{{ site_ui('admission_status.submitted') }}</dt>
                    <dd class="text-gray-900">{{ $admission->submitted_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </dl>

                @if($admission->latestTest && $admission->latestTest->scheduled_at)
                    <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                        <div class="font-semibold">{{ site_ui('admission_status.test_scheduled') }}</div>
                        <div class="mt-1">
                            {{ site_ui('admission_status.date') }}: <span class="font-medium">{{ $admission->latestTest->scheduled_at->format('M j, Y g:i A') }}</span>
                        </div>
                        @if($admission->latestTest->venue)
                            <div>{{ site_ui('admission_status.venue') }}: <span class="font-medium">{{ $admission->latestTest->venue }}</span></div>
                        @endif
                        @if($admission->latestTest->notes)
                            <div class="mt-1 text-blue-800">{{ $admission->latestTest->notes }}</div>
                        @endif
                    </div>
                @endif

                @if($admission->payment_status === 'unpaid' && (float) $admission->admission_fee > 0)
                    <div class="mt-8 rounded-lg border border-amber-300 bg-amber-50 p-5">
                        <h3 class="text-sm font-semibold text-amber-900">{{ __('Submit payment details') }}</h3>
                        <p class="mt-1 text-xs text-amber-800">{{ $settings->payment_instructions ?? __('Send the admission fee and submit your transaction ID below.') }}</p>
                        @if($admission->payment_number)
                            <p class="mt-2 text-xs text-amber-900">{{ __('Payment number') }}: <span class="font-mono font-semibold">{{ $admission->payment_number }}</span></p>
                        @endif
                        <form method="post" action="{{ route('admissions.submit-payment', $admission) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
                            @csrf
                            <div>
                                <label class="block text-xs font-semibold text-amber-900">{{ __('Method') }}</label>
                                <select name="payment_method" required class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm">
                                    <option value="">—</option>
                                    @foreach(\App\Models\Admission::PAYMENT_METHODS as $m)
                                        <option value="{{ $m }}">{{ strtoupper($m) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-amber-900">{{ __('Transaction ID') }}</label>
                                <input type="text" name="transaction_id" required maxlength="128" class="mt-1 w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm font-mono">
                            </div>
                            <div class="sm:col-span-3 flex justify-end">
                                <button class="rounded-md bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">{{ __('Submit payment') }}</button>
                            </div>
                        </form>
                    </div>
                @elseif($admission->payment_status === 'submitted')
                    <div class="mt-8 rounded-lg border border-blue-300 bg-blue-50 p-5 text-sm text-blue-900">
                        <div class="font-semibold">{{ __('Awaiting payment verification') }}</div>
                        <p class="mt-1">{{ __('Your transaction ID') }} <span class="font-mono">{{ $admission->transaction_id }}</span> {{ __('has been submitted. We will verify and email you once confirmed.') }}</p>
                    </div>
                @elseif($admission->payment_status === 'rejected')
                    <div class="mt-8 rounded-lg border border-red-300 bg-red-50 p-5 text-sm text-red-900">
                        <div class="font-semibold">{{ __('Payment could not be verified') }}</div>
                        @if($admission->payment_note)
                            <p class="mt-1">{{ $admission->payment_note }}</p>
                        @endif
                        <p class="mt-1">{{ __('Please contact the school office or submit a new transaction ID.') }}</p>
                        <form method="post" action="{{ route('admissions.submit-payment', $admission) }}" class="mt-4 grid gap-3 sm:grid-cols-3">
                            @csrf
                            <div>
                                <select name="payment_method" required class="w-full rounded-lg border border-red-300 bg-white px-3 py-2 text-sm">
                                    @foreach(\App\Models\Admission::PAYMENT_METHODS as $m)
                                        <option value="{{ $m }}">{{ strtoupper($m) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="sm:col-span-2">
                                <input type="text" name="transaction_id" required maxlength="128" placeholder="{{ __('New transaction ID') }}" class="w-full rounded-lg border border-red-300 bg-white px-3 py-2 text-sm font-mono">
                            </div>
                            <div class="sm:col-span-3 flex justify-end">
                                <button class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Resubmit') }}</button>
                            </div>
                        </form>
                    </div>
                @endif

                <p class="mt-6 text-sm text-gray-600">{{ site_ui('admission_status.follow_up') }}</p>
            @else
                <p class="text-gray-600">{{ site_ui('admission_status.not_found') }}</p>
            @endif
        </div>
    @endif
        </div>
    </div>
@endsection