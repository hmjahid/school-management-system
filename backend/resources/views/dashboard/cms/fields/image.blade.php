{{--
    Image field partial (URL input, single shared value across EN/BN).
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
    <input type="url" id="cms-{{ $name }}" name="{{ $name }}{{ $shared ? '' : '_en' }}" value="{{ $val }}" placeholder="https://…"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    @if(! empty($val))
        <img src="{{ $val }}" alt="" class="mt-2 h-24 rounded border border-gray-200 object-cover">
    @endif
    <p class="mt-1 text-xs text-gray-500">{{ __('Paste an image URL, or upload to the gallery and paste the storage URL.') }}</p>
</div>
@if(! $shared)
<div>
    <label for="cms-{{ $name }}-bn" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }} <span class="ml-1 text-gray-400">(BN)</span>
    </label>
    <input type="url" id="cms-{{ $name }}-bn" name="{{ $name }}_bn" value="{{ $valueBn ?? '' }}" placeholder="https://…"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
    @if(! empty($valueBn))
        <img src="{{ $valueBn }}" alt="" class="mt-2 h-24 rounded border border-gray-200 object-cover">
    @endif
</div>
@endif
