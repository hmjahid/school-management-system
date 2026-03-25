@extends('layouts.app')

@section('title', ($content->title ?? __('Admissions')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? __('Admissions'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            <div class="mt-10 flex flex-wrap gap-3">
                <a href="{{ route('admissions.apply') }}" class="inline-flex rounded-md bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">{{ __('Online application form') }}</a>
                <a href="{{ route('admissions.status') }}" class="inline-flex rounded-md border-2 border-blue-600 px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50">{{ __('Check application status') }}</a>
                <a href="{{ route('site.payments') }}" class="inline-flex rounded-md border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ __('Fee payment portal') }}</a>
            </div>

            <section class="mt-14 rounded-lg border border-gray-200 bg-gray-50 p-6 shadow-md">
                <h2 class="text-lg font-bold text-gray-900">{{ __('Scholarship information request') }}</h2>
                <div class="mt-2 h-1 w-16 bg-orange-500"></div>
                <p class="mt-4 text-sm text-gray-600">{{ __('Tell us about your need and academic background. Our office will respond with next steps.') }}</p>
                <form method="post" action="{{ route('admissions.scholarship.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Full name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ __('Message') }}</label>
                        <textarea name="message" rows="4" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('message') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-md bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">{{ __('Submit scholarship inquiry') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
