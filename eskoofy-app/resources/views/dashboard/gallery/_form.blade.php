@php
    $isEdit = $gallery->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
            <input name="title" value="{{ old('title', $gallery->title) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Description') }}</label>
            <textarea name="description" rows="6" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('description', $gallery->description) }}</textarea>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
            <input name="category" value="{{ old('category', $gallery->category) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="{{ __('e.g. Sports, Annual Function') }}">
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $gallery->is_published))>
                {{ __('Published') }}
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Image') }}</label>
            @include('partials.dashboard.file-button', [
                'name' => 'image',
                'accept' => 'image/*',
                'id' => 'dashboard_gallery_image',
                'buttonLabel' => __('Choose image'),
                'required' => ! $isEdit,
            ])
            @if($isEdit)
                <div class="mt-3 overflow-hidden rounded-lg border border-gray-200 bg-gray-50">
                    <img src="{{ asset('storage/' . $gallery->image_path) }}" alt="{{ $gallery->title }}" class="h-40 w-full object-cover">
                </div>
                <p class="mt-2 text-xs text-gray-500">{{ __('Current:') }} {{ $gallery->image_path }}</p>
            @endif
        </div>
    </div>
</div>

