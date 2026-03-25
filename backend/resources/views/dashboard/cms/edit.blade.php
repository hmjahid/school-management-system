@extends('layouts.dashboard')

@section('title', __('Edit page') . ': ' . $content->page . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
    <div class="mb-6">
        <a href="{{ route('dashboard.cms.pages') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← {{ __('Back to pages') }}</a>
        <h1 class="mt-2 text-2xl font-bold text-gray-900">{{ __('Edit') }}: <span class="font-mono">{{ $content->page }}</span></h1>
        <p class="mt-1 text-sm text-gray-500">{{ __('Content JSON drives structured blocks on the public site. Use valid JSON (object or array).') }}</p>
    </div>

    <form method="post" action="{{ route('dashboard.cms.update', ['page' => $content->page]) }}" class="max-w-4xl space-y-5 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        @method('PUT')

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
            <input type="text" name="title" value="{{ old('title', $content->title) }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta description') }}</label>
                <input type="text" name="meta_description" value="{{ old('meta_description', $content->meta_description) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta keywords') }}</label>
                <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $content->meta_keywords) }}"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">{{ __('Content (JSON)') }}</label>
            <textarea name="content_json" rows="18" required class="w-full rounded-lg border border-gray-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('content_json', $contentJson) }}</textarea>
            @error('content_json')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="is_active" id="is_active" class="size-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($errors->any() ? request()->has('is_active') : ($content->is_active ?? true))>
            <label for="is_active" class="text-sm text-gray-700">{{ __('Page is active') }}</label>
        </div>

        <div class="flex justify-end border-t border-gray-100 pt-4">
            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-indigo-700">
                {{ __('Save page') }}
            </button>
        </div>
    </form>
@endsection
