@extends('layouts.app')

@section('title', __('Application status') . ' — ' . ($siteSettings->school_name ?? config('app.name')))

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => __('Application status'),
            'subtitle' => __('Enter the application number you received after submitting the online form.'),
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">

    <form method="get" action="{{ route('admissions.status') }}" class="flex max-w-xl flex-wrap gap-3">
        <label class="sr-only" for="application_number">{{ __('Application number') }}</label>
        <input type="text" id="application_number" name="application_number" value="{{ $applicationNumber ?? request('application_number') }}"
            placeholder="{{ __('e.g. APP202500001') }}"
            class="min-w-[200px] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
        <button type="submit" class="rounded-md bg-orange-500 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-orange-600">{{ __('Look up') }}</button>
    </form>

    @if($applicationNumber)
        <div class="mt-10 rounded-xl border border-gray-200 bg-gray-50 p-6 shadow-md">
            @if($admission)
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <dt class="font-medium text-gray-500">{{ __('Application number') }}</dt>
                    <dd class="font-mono text-gray-900">{{ $admission->application_number }}</dd>
                    <dt class="font-medium text-gray-500">{{ __('Applicant') }}</dt>
                    <dd class="text-gray-900">{{ $admission->full_name }}</dd>
                    <dt class="font-medium text-gray-500">{{ __('Status') }}</dt>
                    <dd class="text-gray-900">{{ $admission->status_label }}</dd>
                    <dt class="font-medium text-gray-500">{{ __('Submitted') }}</dt>
                    <dd class="text-gray-900">{{ $admission->submitted_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </dl>
                <p class="mt-6 text-sm text-gray-600">{{ __('For fee payment or test scheduling, follow instructions sent to your email or call the admissions office.') }}</p>
                <a href="{{ route('site.payments') }}" class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Fee payment portal') }} →</a>
            @else
                <p class="text-gray-600">{{ __('No application found for that number. Check for typos or contact admissions.') }}</p>
            @endif
        </div>
    @endif
        </div>
    </div>
@endsection
