<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        {{-- English --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ __('English') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Title (English)') }}</label>
                    <input name="title" value="{{ old('title', $announcement->title) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Body (English)') }}</label>
                    <textarea name="body" rows="6" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('body', $announcement->body) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Bengali --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ __('বাংলা') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Title (Bangla)') }}</label>
                    <input name="title_bn" value="{{ old('title_bn', $announcement->title_bn) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Body (Bangla)') }}</label>
                    <textarea name="body_bn" rows="6" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('body_bn', $announcement->body_bn) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Audience') }}</label>
            <div class="mt-2 space-y-2">
                @php
                    $selectedAudience = old('audience', $announcement->audience ?? ['all']);
                    if (!is_array($selectedAudience)) {
                        $selectedAudience = [$selectedAudience];
                    }
                @endphp
                @foreach(['all' => __('All'), 'admin' => __('Admin'), 'teacher' => __('Teacher'), 'student' => __('Student'), 'parent' => __('Parent')] as $value => $label)
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="audience[]" value="{{ $value }}" @checked(in_array($value, $selectedAudience)) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        {{ $label }}
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Show where') }}</label>
            <select name="display_target" class="mt-1 w-full rounded-lg border border-gray-300 px-4 py-2 text-sm">
                @foreach (['header' => __('Website header (ticker bar)'), 'notification' => __('Dashboard notifications'), 'both' => __('Both header and notifications')] as $k => $lbl)
                    <option value="{{ $k }}" @selected(old('display_target', $announcement->display_target) === $k)>{{ $lbl }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-gray-500">{{ __('Choose where this announcement should be displayed.') }}</p>
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
