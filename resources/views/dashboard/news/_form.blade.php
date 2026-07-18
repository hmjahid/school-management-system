@php
    $isEdit = $news->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
            <input name="title" value="{{ old('title', $news->title) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Slug') }}</label>
            <input name="slug" value="{{ old('slug', $news->slug) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="{{ __('auto-generated if empty') }}">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Content') }}</label>
            <textarea name="content" rows="12" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('content', $news->content) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">{{ __('Tip: You can paste plain text or basic HTML.') }}</p>
        </div>
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $news->is_published))>
                {{ __('Published') }}
            </label>
            <label class="mt-3 flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="is_event" value="1" @checked(old('is_event', $news->is_event))>
                {{ __('This is an event') }}
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
            <input name="category" value="{{ old('category', $news->category) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Published at') }}</label>
            <input type="date" name="published_at" value="{{ old('published_at', $news->published_at?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Event date') }}</label>
            <input type="date" name="event_date" value="{{ old('event_date', $news->event_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Event location') }}</label>
            <input name="event_location" value="{{ old('event_location', $news->event_location) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Author name') }}</label>
            <input name="author_name" value="{{ old('author_name', $news->author_name) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Image') }}</label>
            @include('partials.dashboard.file-button', [
                'name' => 'image',
                'accept' => 'image/*',
                'id' => 'dashboard_news_image',
                'buttonLabel' => __('Choose image'),
            ])
            @if($isEdit && $news->image_url)
                <p class="mt-2 text-xs text-gray-500">{{ __('Current:') }} {{ $news->image_url }}</p>
            @endif
        </div>
    </div>
</div>

