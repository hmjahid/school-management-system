{{--
    Textarea field partial.
    Variables: $name, $field, $valueEn, $valueBn, $rows (default 4)
--}}
@php
    $req = ! empty($field['required']);
    $rows = $rows ?? ($field['rows'] ?? 4);
@endphp
<div>
    <label for="cms-{{ $name }}-en" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }}
        @if($req)<span class="text-red-500">*</span>@endif
        <span class="ml-1 text-gray-400">(EN)</span>
    </label>
    <textarea id="cms-{{ $name }}-en" name="{{ $name }}_en" rows="{{ $rows }}" @if($req) required @endif
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ $valueEn ?? '' }}</textarea>
</div>
<div>
    <label for="cms-{{ $name }}-bn" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }}
        @if($req)<span class="text-red-500">*</span>@endif
        <span class="ml-1 text-gray-400">(BN)</span>
    </label>
    <textarea id="cms-{{ $name }}-bn" name="{{ $name }}_bn" rows="{{ $rows }}"
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ $valueBn ?? '' }}</textarea>
</div>
