{{--
    Image field partial (URL input + file upload, single shared value across EN/BN).
    Variables: $name, $field, $value (shared), $valueEn, $valueBn
--}}
@php
    $shared = $field['shared'] ?? false;
    $val = $shared ? ($value ?? '') : ($valueEn ?? '');
@endphp
<div>
    <label for="cms-{{ $name }}" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }}
        @if($shared) <span class="ml-1 text-gray-400">(shared)</span> @endif
    </label>
    <input type="file" id="cms-{{ $name }}-file" name="{{ $name }}{{ $shared ? '' : '_en' }}"
        accept="image/*"
        class="mb-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100 focus:border-indigo-500 focus:outline-none">
    <input type="url" id="cms-{{ $name }}" name="{{ $name }}{{ $shared ? '' : '_en' }}" value="{{ $val }}" placeholder="https://…"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    <button type="button" onclick="openMediaBrowser(this)" class="mt-1 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Browse Media
    </button>
    @if(! empty($val))
        <img src="{{ $val }}" alt="" class="mt-2 h-24 rounded border border-gray-200 object-cover">
    @endif
    <p class="mt-1 text-xs text-gray-500">{{ __('Upload a file, or paste an image URL.') }}</p>
</div>
@if(! $shared)
<div>
    <label for="cms-{{ $name }}-bn" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }} <span class="ml-1 text-gray-400">(বাংলা)</span>
    </label>
    <input type="file" id="cms-{{ $name }}-bn-file" name="{{ $name }}_bn"
        accept="image/*"
        class="mb-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100 focus:border-indigo-500 focus:outline-none">
    <input type="url" id="cms-{{ $name }}-bn" name="{{ $name }}_bn" value="{{ $valueBn ?? '' }}" placeholder="https://…"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    <button type="button" onclick="openMediaBrowser(this)" class="mt-1 inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        Browse Media
    </button>
    @if(! empty($valueBn))
        <img src="{{ $valueBn }}" alt="" class="mt-2 h-24 rounded border border-gray-200 object-cover">
    @endif
</div>
@endif
