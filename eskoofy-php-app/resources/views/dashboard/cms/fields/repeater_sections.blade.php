{{--
    Repeater for generic "intro + heading + paragraphs" sections used by
    about / academics / admissions / contact / terms / privacy etc.
    Variables: $name (default "sections"), $rowsEn, $rowsBn
--}}
@php
    $name = $name ?? 'sections';
    $rowsEn = $rowsEn ?? [];
    $rowsBn = $rowsBn ?? [];
    $wrapId = 'cms-rs-'.preg_replace('/[^a-z0-9]/i', '-', $name);
@endphp
<div class="space-y-4" data-cms-repeater="{{ $wrapId }}" data-cms-repeater-name="{{ $name }}">
    @forelse ($rowsEn as $idx => $rowEn)
        @php
            $rowBn = $rowsBn[$idx] ?? [];
            $parasEn = is_array($rowEn['paragraphs'] ?? null) ? implode("\n\n", $rowEn['paragraphs']) : ($rowEn['paragraphs'] ?? '');
            $parasBn = is_array($rowBn['paragraphs'] ?? null) ? implode("\n\n", $rowBn['paragraphs']) : ($rowBn['paragraphs'] ?? '');
        @endphp
        <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Section') }} #{{ $idx + 1 }}</span>
                <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                @include('dashboard.cms.fields._pair-text', [
                    'name' => $name.'['.$idx.'][heading_en]',
                    'field' => ['key' => 'heading', 'label' => __('Heading')],
                    'valueEn' => $rowEn['heading'] ?? '',
                    'valueBn' => $rowBn['heading'] ?? '',
                ])
                @include('dashboard.cms.fields._pair-textarea', [
                    'name' => $name.'['.$idx.'][paragraphs_en]',
                    'field' => ['key' => 'paragraphs', 'label' => __('Body (one paragraph per blank line)')],
                    'valueEn' => $parasEn,
                    'valueBn' => $parasBn,
                    'rows' => 5,
                ])
            </div>
        </div>
    @empty
        <p class="text-xs text-gray-500">{{ __('No sections yet.') }}</p>
    @endforelse
</div>
<template data-cms-rep-template="{{ $wrapId }}">
    <div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
        <div class="mb-3 flex items-center justify-between">
            <span class="cms-rep-label text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('Section') }} #__INDEX__</span>
            <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
        </div>
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Heading') }} <span class="ml-1 text-gray-400">(EN)</span></label>
                <input type="text" name="__NAME__[__INDEX__][heading_en]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Heading') }} <span class="ml-1 text-gray-400">(বাংলা)</span></label>
                <input type="text" name="__NAME__[__INDEX__][heading_bn]" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Body (one paragraph per blank line)') }} <span class="ml-1 text-gray-400">(EN)</span></label>
                <textarea name="__NAME__[__INDEX__][paragraphs_en]" rows="5" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600">{{ __('Body (one paragraph per blank line)') }} <span class="ml-1 text-gray-400">(বাংলা)</span></label>
                <textarea name="__NAME__[__INDEX__][paragraphs_bn]" rows="5" class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm"></textarea>
            </div>
        </div>
    </div>
</template>
