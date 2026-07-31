{{--
    Select field partial (single shared value across EN/BN).
    Variables: $name, $field, $value, $valueEn, $valueBn
--}}
@php
    $options = $field['options'] ?? [];
    $val = old($name, $value ?? $valueEn ?? '');
@endphp
<div>
    <label for="cms-{{ $name }}" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }}
    </label>
    <select id="cms-{{ $name }}" name="{{ $name }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
        <option value="">—</option>
        @foreach($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected((string) $val === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>
</div>
