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
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <dt class="font-medium text-gray-500">{{ site_ui('admission_status.application_number') }}</dt>
                    <dd class="font-mono text-gray-900">{{ $admission->application_number }}</dd>
                    <dt class="font-medium text-gray-500">{{ site_ui('admission_status.applicant') }}</dt>
                    <dd class="text-gray-900">{{ $admission->full_name }}</dd>
                    <dt class="font-medium text-gray-500">{{ site_ui('admission_status.status') }}</dt>
                    <dd class="text-gray-900">{{ $admission->status_label }}</dd>
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
                <p class="mt-6 text-sm text-gray-600">{{ site_ui('admission_status.follow_up') }}</p>
                <a href="{{ route('site.payments') }}" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ site_ui('admission_status.fee_portal') }} →</a>
            @else
                <p class="text-gray-600">{{ site_ui('admission_status.not_found') }}</p>
            @endif
        </div>
    @endif
        </div>
    </div>
@endsection
