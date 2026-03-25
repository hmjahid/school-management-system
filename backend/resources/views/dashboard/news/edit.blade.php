@extends('layouts.dashboard')

@section('title', __('Edit news') . ' — ' . config('app.name'))

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit news') }}</h1>
            <p class="mt-1 text-sm text-gray-600">{{ $news->title }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard.news.index') }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Back') }}</a>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-end gap-3 mb-4">
        <form method="post" action="{{ route('dashboard.news.destroy', $news) }}" onsubmit="return confirm('{{ __('Delete this item?') }}')">
            @csrf
            @method('delete')
            <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-900 hover:bg-red-100">{{ __('Delete') }}</button>
        </form>
    </div>

    <form method="post" action="{{ route('dashboard.news.update', $news) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('put')
        @include('dashboard.partials.form-errors')
        @include('dashboard.news._form', ['news' => $news])

        <div class="flex items-center gap-3">
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Save changes') }}</button>
        </div>
    </form>
@endsection

