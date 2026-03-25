<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Title') }}</label>
            <input name="title" value="{{ old('title', $announcement->title) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Body') }}</label>
            <textarea name="body" rows="8" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('body', $announcement->body) }}</textarea>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Audience') }}</label>
            <select name="audience" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                @foreach (['all' => __('All'), 'student' => __('Students'), 'parent' => __('Parents')] as $k => $lbl)
                    <option value="{{ $k }}" @selected(old('audience', $announcement->audience) === $k)>{{ $lbl }}</option>
                @endforeach
            </select>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $announcement->is_published))>
                {{ __('Published') }}
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Starts at') }}</label>
            <input type="date" name="starts_at" value="{{ old('starts_at', $announcement->starts_at?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Ends at') }}</label>
            <input type="date" name="ends_at" value="{{ old('ends_at', $announcement->ends_at?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
    </div>
</div>

