@php
    $req = ! empty($field['required']);
    if (isset($nameBn) && $nameBn !== null && $nameBn !== '') {
        $nameEn = $name;
        $nameBnField = $nameBn;
    } elseif (str_ends_with($name, '_en')) {
        $nameEn = $name;
        $nameBnField = substr($name, 0, -3) . '_bn';
    } else {
        $nameEn = $name . '_en';
        $nameBnField = $name . '_bn';
    }
@endphp
<div>
    <label class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($field['key'] ?? '') }}
        @if($req)<span class="text-red-500">*</span>@endif
        <span class="ml-1 text-gray-400">(EN)</span>
    </label>
    <input type="text" name="{{ $nameEn }}" value="{{ $valueEn ?? '' }}" @if($req) required @endif
        class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
</div>
<div>
    <label class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($field['key'] ?? '') }}
        @if($req)<span class="text-red-500">*</span>@endif
        <span class="ml-1 text-gray-400">(BN)</span>
    </label>
    <input type="text" name="{{ $nameBnField }}" value="{{ $valueBn ?? '' }}"
        class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
</div>
