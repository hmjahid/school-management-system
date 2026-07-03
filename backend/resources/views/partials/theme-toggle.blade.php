@php
    $themeLabel = __('Toggle theme');
@endphp
<button type="button" data-theme-toggle aria-pressed="false" aria-label="{{ $themeLabel }}" title="{{ $themeLabel }}"
    class="inline-flex items-center justify-center rounded-md p-2 text-gray-500 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white">
    <span data-theme-label aria-hidden="true" class="text-base leading-none">☾</span>
</button>
