{{--
    List field partial: paired parallel lists of single-line strings.
    Variables: $name, $field, $listEn, $listBn, $itemLabel
--}}
@php
    $listEn = $listEn ?? [];
    $listBn = $listBn ?? [];
    $itemLabel = $itemLabel ?? 'Item';
    $id = 'cms-list-'.preg_replace('/[^a-z0-9]/i', '-', $name);
    $n = max(count($listEn), count($listBn));
    $rowsEn = [];
    $rowsBn = [];
    for ($i = 0; $i < $n; $i++) {
        $rowsEn[] = $listEn[$i] ?? '';
        $rowsBn[] = $listBn[$i] ?? '';
    }
@endphp
<div class="space-y-2" data-cms-list="{{ $id }}" data-name="{{ $name }}">
    <div class="grid grid-cols-12 gap-2 text-xs font-semibold text-gray-500">
        <div class="col-span-1">#</div>
        <div class="col-span-5">EN</div>
        <div class="col-span-5">বাংলা</div>
        <div class="col-span-1"></div>
    </div>
    @foreach ($rowsEn as $i => $valEn)
        <div class="cms-list-row grid grid-cols-12 gap-2">
            <div class="col-span-1 flex items-center text-xs text-gray-400">{{ $i + 1 }}</div>
            <div class="col-span-5">
                <input type="text" name="{{ $name }}_en[]" value="{{ $valEn }}" placeholder="{{ $itemLabel }}"
                    class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div class="col-span-5">
                <input type="text" name="{{ $name }}_bn[]" value="{{ $rowsBn[$i] ?? '' }}" placeholder="{{ $itemLabel }}"
                    class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <button type="button" data-cms-list-remove class="text-xs text-red-600 hover:text-red-800">×</button>
            </div>
        </div>
    @endforeach
    @if(count($rowsEn) === 0)
        <div class="cms-list-row grid grid-cols-12 gap-2">
            <div class="col-span-1 flex items-center text-xs text-gray-400">1</div>
            <div class="col-span-5">
                <input type="text" name="{{ $name }}_en[]" value="" placeholder="{{ $itemLabel }}"
                    class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div class="col-span-5">
                <input type="text" name="{{ $name }}_bn[]" value="" placeholder="{{ $itemLabel }}"
                    class="w-full rounded border border-gray-300 px-2 py-1.5 text-sm">
            </div>
            <div class="col-span-1 flex items-center justify-end">
                <button type="button" data-cms-list-remove class="text-xs text-red-600 hover:text-red-800">×</button>
            </div>
        </div>
    @endif
</div>
<button type="button" data-cms-list-add="{{ $id }}" data-name="{{ $name }}" data-item-label="{{ $itemLabel }}"
    class="mt-2 inline-flex items-center gap-1 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
    <span class="text-base leading-none">+</span> {{ __('Add') }} {{ $itemLabel }}
</button>
