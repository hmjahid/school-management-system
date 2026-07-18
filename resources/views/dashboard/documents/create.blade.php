@extends('layouts.dashboard')

@section('title', __('New document') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Create document') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ __('Upload a file and link it from CMS content blocks.') }}</p>
        </div>
        <a href="{{ route('dashboard.documents.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
    </div>

    <form method="post" action="{{ route('dashboard.documents.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @include('dashboard.partials.form-errors')
        @include('dashboard.documents._form', ['document' => $document])
        <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save') }}</button>
    </form>
@endsection

