{{--
  Visually prominent file picker: solid button + hidden input (dashboard forms).

  @param string $name
  @param string|null $accept
  @param string|null $id  Stable id avoids duplicate-id if omitted uses hash of name+accept
  @param string|null $buttonLabel  Defaults to "Choose file"
  @param bool $required
  @param string|null $previewId  If set, a live thumbnail preview is shown after picking an image.
  @param string|null $previewSrc  Optional existing image URL to show before any file is chosen.
--}}
@php
    $fileId = $id ?? 'dash_file_' . str_replace('.', '', uniqid('', true));
@endphp
<div class="{{ $wrapperClass ?? 'mt-1' }}">
    <label for="{{ $fileId }}" class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-indigo-700 focus-within:outline focus-within:ring-2 focus-within:ring-indigo-500 focus-within:ring-offset-2">
        <svg class="h-5 w-5 shrink-0 opacity-95" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
        </svg>
        <span>{{ $buttonLabel ?? __('Choose file') }}</span>
        <input
            type="file"
            name="{{ $name }}"
            id="{{ $fileId }}"
            class="sr-only"
            @if(! empty($accept)) accept="{{ $accept }}" @endif
            @if(! empty($required)) required @endif
            @if(! empty($previewId)) data-image-preview="{{ $previewId }}" @endif
        >
    </label>
    @if(! empty($previewId))
        <img
            id="{{ $previewId }}"
            src="{{ $previewSrc ?? '' }}"
            alt="{{ __('Preview') }}"
            class="mt-3 h-32 w-full max-w-xs rounded-lg border border-gray-200 object-cover {{ ! empty($previewSrc) ? '' : 'hidden' }}"
        >
    @endif
</div>
