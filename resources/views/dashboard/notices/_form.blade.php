@php
    $isEdit = $notice->exists;
@endphp

<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2 space-y-4">
        {{-- English --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ __('English') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Title (English)') }}</label>
                    <input name="title" value="{{ old('title', $notice->title) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Content (English)') }}</label>
                    <textarea name="content" rows="10" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('content', $notice->content) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Bengali --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <h3 class="mb-3 text-sm font-semibold text-gray-500 uppercase tracking-wider">{{ __('বাংলা') }}</h3>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Title (Bangla)') }}</label>
                    <input name="title_bn" value="{{ old('title_bn', $notice->title_bn) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Content (Bangla)') }}</label>
                    <textarea name="content_bn" rows="10" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">{{ old('content_bn', $notice->content_bn) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 space-y-3">
            <label class="flex items-center gap-2 text-sm font-medium text-gray-800">
                <input type="checkbox" name="pinned" value="1" @checked(old('pinned', $notice->pinned))>
                {{ __('Pinned') }}
            </label>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">{{ __('Audience') }}</label>
            <div class="mt-2 space-y-2">
                @php
                    $selectedAudience = old('audience', $notice->audience ?? ['all']);
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
    </div>
</div>
