{{--
    Contact cards repeater (label + phone per row).
--}}
@php
    $rowsEn = $rowsEn ?? [];
    $rowsBn = $rowsBn ?? [];
    $name = $name ?? 'emergency_contacts';
    $wrapId = 'cms-cc-'.preg_replace('/[^a-z0-9]/i', '-', $name);
@endphp
<div class="space-y-3" data-cms-repeater="{{ $wrapId }}" data-cms-repeater-name="{{ $name }}">
    @forelse ($rowsEn as $idx => $rowEn)
        @php $rowBn = $rowsBn[$idx] ?? []; @endphp
        <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Contact') }} #{{ $idx + 1 }}</span>
                <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @include('dashboard.cms.fields._pair-text', [
                    'name' => $name.'['.$idx.'][label_en]',
                    'nameBn' => $name.'['.$idx.'][label_bn]',
                    'field' => ['key' => 'label', 'label' => __('Label')],
                    'valueEn' => $rowEn['label'] ?? '',
                    'valueBn' => $rowBn['label'] ?? '',
                ])
                @include('dashboard.cms.fields._pair-text', [
                    'name' => $name.'['.$idx.'][phone_en]',
                    'nameBn' => $name.'['.$idx.'][phone_bn]',
                    'field' => ['key' => 'phone', 'label' => __('Phone')],
                    'valueEn' => $rowEn['phone'] ?? '',
                    'valueBn' => $rowBn['phone'] ?? '',
                ])
            </div>
        </div>
    @empty
        <p class="text-xs text-gray-500">{{ __('No emergency contacts yet.') }}</p>
    @endforelse
</div>
<template data-cms-rep-template="{{ $wrapId }}">
    <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="mb-3 flex items-center justify-between">
            <span class="cms-rep-label text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Contact') }} #__INDEX__</span>
            <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Label') }} <span class="ml-1 text-gray-400">(EN)</span></label>
                <input type="text" name="__NAME__[__INDEX__][label_en]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Label') }} <span class="ml-1 text-gray-400">(BN)</span></label>
                <input type="text" name="__NAME__[__INDEX__][label_bn]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Phone') }} <span class="ml-1 text-gray-400">(EN)</span></label>
                <input type="text" name="__NAME__[__INDEX__][phone_en]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Phone') }} <span class="ml-1 text-gray-400">(BN)</span></label>
                <input type="text" name="__NAME__[__INDEX__][phone_bn]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
        </div>
    </div>
</template>
<button type="button" data-cms-rep-add="{{ $wrapId }}" data-name="{{ $name }}" data-item-label="{{ __('Contact') }}"
    class="mt-2 inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
    <span class="text-base leading-none">+</span> {{ __('Add contact') }}
</button>
