@extends('layouts.dashboard')

@section('title', __('Edit Testimonial') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit Testimonial') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Update testimonial details.') }}</p>
        </div>
        <a href="{{ route('dashboard.testimonials.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
    </div>

    <form method="post" action="{{ route('dashboard.testimonials.update', $testimonial) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('put')
        @include('dashboard.testimonials._form', compact('testimonial'))

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
        </div>
    </form>
@endsection
