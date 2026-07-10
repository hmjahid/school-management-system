<section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <header class="mb-4">
        <h2 class="text-base font-semibold text-gray-900">{{ __('Page details') }}</h2>
        <p class="mt-1 text-xs text-gray-500">{{ __('These appear in browser tabs and search results. The বাংলা title is optional; leave empty to use the English one.') }}</p>
    </header>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="cms-title-en" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Page title (English)') }} <span class="text-red-500">*</span></label>
            <input type="text" id="cms-title-en" name="title_en" value="{{ old('title_en', $content->title_en ?? $content->title) }}" required
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <div>
            <label for="cms-title-bn" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Page title (বাংলা)') }}</label>
            <input type="text" id="cms-title-bn" name="title_bn" value="{{ old('title_bn', $content->title_bn ?? '') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        </div>
        <div>
            <label for="cms-meta-en" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta description (English)') }}</label>
            <textarea id="cms-meta-en" name="meta_description_en" rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('meta_description_en', $content->meta_description_en ?? '') }}</textarea>
        </div>
        <div>
            <label for="cms-meta-bn" class="mb-1 block text-sm font-medium text-gray-700">{{ __('Meta description (বাংলা)') }}</label>
            <textarea id="cms-meta-bn" name="meta_description_bn" rows="2"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ old('meta_description_bn', $content->meta_description_bn ?? '') }}</textarea>
        </div>
    </div>
</section>
