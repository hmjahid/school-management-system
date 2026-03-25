@extends('layouts.app')

@section('title', ($content->title ?? site_ui('nav.admissions')) . ' — ' . ($siteSettings->school_name ?? config('app.name')))
@section('meta_description', $content->meta_description)

@section('content')
    <div class="bg-white">
        @include('site.partials.inner-hero', [
            'title' => $content->title ?? site_ui('nav.admissions'),
            'subtitle' => $content->meta_description,
        ])
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            @include('site.partials.sections', ['content' => $content])

            <div class="mt-10 flex flex-wrap gap-3">
                <a href="{{ route('admissions.apply') }}" class="inline-flex rounded-md bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">{{ site_ui('admissions_landing.cta_apply') }}</a>
                <a href="{{ route('admissions.status') }}" class="inline-flex rounded-md border-2 border-blue-600 px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50">{{ site_ui('admissions_landing.cta_status') }}</a>
                <a href="{{ route('site.payments') }}" class="inline-flex rounded-md border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-800 hover:bg-gray-50">{{ site_ui('admissions_landing.cta_payments') }}</a>
            </div>

            <section class="mt-14 rounded-lg border border-gray-200 bg-gray-50 p-6 shadow-md">
                <h2 class="text-lg font-bold text-gray-900">{{ site_ui('admissions_landing.scholarship_title') }}</h2>
                <div class="mt-2 h-1 w-16 bg-orange-500"></div>
                <p class="mt-4 text-sm text-gray-600">{{ site_ui('admissions_landing.scholarship_intro') }}</p>
                <form method="post" action="{{ route('admissions.scholarship.store') }}" class="mt-6 grid gap-4 sm:grid-cols-2">
                    @csrf
                    <input type="text" name="website" value="" class="hidden" tabindex="-1" autocomplete="off" aria-hidden="true">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('admissions_landing.scholarship_full_name') }}</label>
                        <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('admissions_landing.scholarship_email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('admissions_landing.scholarship_phone') }}</label>
                        <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-gray-700">{{ site_ui('admissions_landing.scholarship_message') }}</label>
                        <textarea name="message" rows="4" required class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('message') }}</textarea>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="submit" class="rounded-md bg-orange-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-orange-600">{{ site_ui('admissions_landing.scholarship_submit') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection
