@php
    $isEdit = $testimonial->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Author Name') }}</label>
            <input name="author_name" value="{{ old('author_name', $testimonial->author_name) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Designation') }}</label>
            <input name="author_designation" value="{{ old('author_designation', $testimonial->author_designation) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="e.g. Student, Parent">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Content') }}</label>
            <textarea name="content" rows="6" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('content', $testimonial->content) }}</textarea>
        </div>
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Rating') }}</label>
                <div class="mt-1 flex items-center gap-1">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer">
                            <input type="radio" name="rating" value="{{ $i }}" {{ old('rating', $testimonial->rating) == $i ? 'checked' : '' }} class="sr-only">
                            <svg class="h-6 w-6 {{ old('rating', $testimonial->rating) >= $i ? 'text-amber-400' : 'text-gray-300' }} hover:text-amber-400 transition" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                        </label>
                    @endfor
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Photo') }}</label>
                <input type="file" name="photo" accept="image/*" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @if($isEdit && $testimonial->photo)
                    <p class="mt-1 text-xs text-gray-500">{{ __('Current:') }} {{ basename($testimonial->photo) }}</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Sort Order') }}</label>
                <input name="sort_order" type="number" min="0" value="{{ old('sort_order', $testimonial->sort_order ?? 0) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>

            <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="is_visible" value="1" @checked(old('is_visible', $testimonial->is_visible ?? true)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                {{ __('Visible on website') }}
            </label>
        </div>
    </div>
</div>
