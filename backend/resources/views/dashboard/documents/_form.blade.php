<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
            <input name="title" value="{{ old('title', $document->title) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
            <input name="category" value="{{ old('category', $document->category) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="{{ __('e.g. Policies, Forms, Newsletter') }}">
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $document->is_published))>
                {{ __('Published') }}
            </label>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('File') }}</label>
            @include('partials.dashboard.file-button', [
                'name' => 'file',
                'id' => 'dashboard_document_file',
                'buttonLabel' => __('Choose file'),
                'required' => ! $document->exists,
            ])
            @if($document->exists)
                <p class="mt-2 text-xs text-gray-500">{{ __('Current:') }} {{ $document->file_path }}</p>
                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" rel="noreferrer" class="mt-1 inline-block text-xs font-medium text-blue-600 hover:underline">{{ __('Open current file') }}</a>
            @endif
        </div>
    </div>
</div>

