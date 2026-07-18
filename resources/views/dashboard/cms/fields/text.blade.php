{{--
    Text field partial.
    Variables:
      - $name: base field name (no locale suffix)
      - $field: ['key' => ..., 'label' => ..., 'required' => bool]
      - $valueEn, $valueBn: current values for EN and BN
      - $placeholderEn, $placeholderBn: optional placeholders
--}}
@php
    $req = ! empty($field['required']);
    $phEn = $placeholderEn ?? '';
    $phBn = $placeholderBn ?? '';
@endphp
<div>
    <label for="cms-{{ $name }}-en" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }}
        @if($req)<span class="text-red-500">*</span>@endif
        <span class="ml-1 text-gray-400">(EN)</span>
    </label>
    <input type="text" id="cms-{{ $name }}-en" name="{{ $name }}_en" value="{{ $valueEn ?? '' }}" @if($phEn) placeholder="{{ $phEn }}" @endif @if($req) required @endif
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
</div>
<div>
    <label for="cms-{{ $name }}-bn" class="mb-1 block text-xs font-medium text-gray-600">
        {{ $field['label'] ?? ucfirst($name) }}
        @if($req)<span class="text-red-500">*</span>@endif
        <span class="ml-1 text-gray-400">(বাংলা)</span>
    </label>
    <input type="text" id="cms-{{ $name }}-bn" name="{{ $name }}_bn" value="{{ $valueBn ?? '' }}" @if($phBn) placeholder="{{ $phBn }}" @endif
        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
</div>
