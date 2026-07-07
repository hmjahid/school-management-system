{{--
    Repeater field partial. Generic typed repeater.
    Variables:
      - $name: base name (e.g. "testimonials")
      - $field: registry entry with 'fields' => [{key,label,type,required?}]
      - $rowsEn, $rowsBn: arrays of associative arrays
      - $itemLabel: e.g. "Testimonial"
      - $idPrefix: unique DOM id prefix
--}}
@php
    $rowsEn = $rowsEn ?? [];
    $rowsBn = $rowsBn ?? [];
    $itemLabel = $itemLabel ?? 'Item';
    $fields = $field['fields'] ?? [];
    $wrapId = $idPrefix ?? ('cms-rep-'.preg_replace('/[^a-z0-9]/i', '-', $name));
@endphp
<div class="space-y-3" data-cms-repeater="{{ $wrapId }}" data-cms-repeater-name="{{ $name }}">
    @forelse ($rowsEn as $idx => $rowEn)
        @php
            $rowBn = $rowsBn[$idx] ?? [];
        @endphp
        <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $itemLabel }} #{{ $idx + 1 }}</span>
                <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($fields as $sub)
                    @php
                        $subName = $name.'['.$idx.']['.$sub['key'].'_en]';
                        $subBnName = $name.'['.$idx.']['.$sub['key'].'_bn]';
                        $subEn = old($subName, $rowEn[$sub['key']] ?? '');
                        $subBn = old($subBnName, $rowBn[$sub['key']] ?? '');
                    @endphp
                    @if(($sub['type'] ?? 'text') === 'textarea')
                        @include('dashboard.cms.fields._pair-textarea', [
                            'name' => $subName,
                            'nameBn' => $subBnName,
                            'field' => $sub,
                            'valueEn' => $subEn,
                            'valueBn' => $subBn,
                            'rows' => $sub['rows'] ?? 3,
                        ])
                    @else
                        @include('dashboard.cms.fields._pair-text', [
                            'name' => $subName,
                            'nameBn' => $subBnName,
                            'field' => $sub,
                            'valueEn' => $subEn,
                            'valueBn' => $subBn,
                        ])
                    @endif
                @endforeach
            </div>
        </div>
    @empty
        <div class="cms-rep-row rounded-lg border border-dashed border-gray-300 bg-gray-50 p-4 text-center text-xs text-gray-500">
            {{ __('No items yet. Click "Add" to create one.') }}
        </div>
    @endforelse
</div>
<template data-cms-rep-template="{{ $wrapId }}">
    <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="mb-3 flex items-center justify-between">
            <span class="cms-rep-label text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $itemLabel }} #__INDEX__</span>
            <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($fields as $sub)
                <div data-cms-rep-slot="{{ $sub['key'] }}">
                    @if(($sub['type'] ?? 'text') === 'textarea')
                        <label class="mb-1 block text-xs font-medium text-gray-600">{{ $sub['label'] }} <span class="ml-1 text-gray-400">(EN)</span></label>
                        <textarea name="__NAME__[__INDEX__][{{ $sub['key'] }}_en]" rows="{{ $sub['rows'] ?? 3 }}"
                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                    @else
                        <label class="mb-1 block text-xs font-medium text-gray-600">{{ $sub['label'] }} <span class="ml-1 text-gray-400">(EN)</span></label>
                        <input type="text" name="__NAME__[__INDEX__][{{ $sub['key'] }}_en]" value=""
                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                    @endif
                </div>
                <div data-cms-rep-slot="{{ $sub['key'] }}">
                    <label class="mb-1 block text-xs font-medium text-gray-600">{{ $sub['label'] }} <span class="ml-1 text-gray-400">(BN)</span></label>
                    @if(($sub['type'] ?? 'text') === 'textarea')
                        <textarea name="__NAME__[__INDEX__][{{ $sub['key'] }}_bn]" rows="{{ $sub['rows'] ?? 3 }}"
                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
                    @else
                        <input type="text" name="__NAME__[__INDEX__][{{ $sub['key'] }}_bn]" value=""
                            class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</template>
<button type="button" data-cms-rep-add="{{ $wrapId }}" data-name="{{ $name }}" data-item-label="{{ $itemLabel }}"
    class="mt-2 inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
    <span class="text-base leading-none">+</span> {{ __('Add') }} {{ $itemLabel }}
</button>
