{{--
    Slider field partial. Typed repeater where each slide has a shared image
    plus title/caption/link text pairs.
    Variables:
      - $name: base name (e.g. "slider")
      - $field: registry entry with 'fields' => [{key,label,type,required?}]
      - $rowsEn, $rowsBn: arrays of associative arrays
      - $idPrefix: unique DOM id prefix
--}}
@php
    $rowsEn = $rowsEn ?? [];
    $rowsBn = $rowsBn ?? [];
    $itemLabel = $itemLabel ?? 'Slide';
    $fields = $field['fields'] ?? [];
    $wrapId = $idPrefix ?? ('cms-rep-'.preg_replace('/[^a-z0-9]/i', '-', $name));
@endphp
<div class="space-y-3" data-cms-repeater="{{ $wrapId }}" data-cms-repeater-name="{{ $name }}">
    @forelse ($rowsEn as $idx => $rowEn)
        @include('dashboard.cms.fields._slider-row', [
            'name' => $name,
            'index' => $idx,
            'fields' => $fields,
            'rowEn' => $rowEn,
            'rowBn' => $rowsBn[$idx] ?? [],
            'itemLabel' => $itemLabel,
        ])
    @empty
        <div class="cms-rep-row rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-500">
            {{ __('No slides yet. Click "Add" to create one.') }}
        </div>
    @endforelse
</div>
<template data-cms-rep-template="{{ $wrapId }}">
    @include('dashboard.cms.fields._slider-row', [
        'name' => '__NAME__',
        'index' => '__INDEX__',
        'fields' => $fields,
        'rowEn' => [],
        'rowBn' => [],
        'itemLabel' => $itemLabel,
    ])
</template>
<button type="button" data-cms-rep-add="{{ $wrapId }}" data-name="{{ $name }}" data-item-label="{{ $itemLabel }}"
    class="mt-2 inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
    <span class="text-base leading-none">+</span> {{ __('Add') }} {{ $itemLabel }}
</button>
