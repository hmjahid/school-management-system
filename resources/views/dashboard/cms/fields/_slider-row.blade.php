{{--
    Single slider row used by both the initial render and the add-row template.
    Variables:
      - $name: base name ("slider", or "__NAME__" inside the template)
      - $index: 0-based index ("__INDEX__" inside the template)
      - $fields: [{key,label,type,shared?,required?}]
      - $rowEn, $rowBn: arrays of values
      - $itemLabel: human label
--}}
@php
    $rowEn = $rowEn ?? [];
    $rowBn = $rowBn ?? [];
    $fields = $fields ?? [];
@endphp
<div class="cms-rep-row rounded-lg border border-gray-200 bg-gray-50 p-4">
    <div class="mb-3 flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $itemLabel }} #{{ is_int($index) ? $index + 1 : $index }}</span>
        <button type="button" data-cms-rep-remove class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('Remove') }}</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($fields as $sub)
            @php
                $subKey = $sub['key'];
                $isImage = ($sub['type'] ?? 'text') === 'image';
                $fieldName = $name.'['.$index.']['.$subKey.']';
                $fieldNameEn = $name.'['.$index.']['.$subKey.'_en]';
                $fieldNameBn = $name.'['.$index.']['.$subKey.'_bn]';
                $valEn = $rowEn[$subKey] ?? '';
                $valBn = $rowBn[$subKey] ?? '';
            @endphp
            @if($isImage)
                <div class="sm:col-span-2">
                    <label class="mb-1 block text-xs font-medium text-gray-600">
                        {{ $sub['label'] ?? ucfirst($subKey) }} <span class="ml-1 text-gray-400">(shared)</span>
                    </label>
                    <input type="file" name="{{ $fieldName }}" accept="image/*"
                        class="mb-2 block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-600 hover:file:bg-indigo-100 focus:border-indigo-500 focus:outline-none">
                    <input type="url" name="{{ $fieldName }}" value="{{ $valEn }}" placeholder="https://…"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    @if(! empty($valEn))
                        <img src="{{ $valEn }}" alt="" class="mt-2 h-24 rounded border border-gray-200 object-cover">
                    @endif
                    <p class="mt-1 text-xs text-gray-500">{{ __('Upload a file, or paste an image URL.') }}</p>
                </div>
            @else
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">
                        {{ $sub['label'] ?? ucfirst($subKey) }} <span class="ml-1 text-gray-400">(EN)</span>
                    </label>
                    <input type="text" name="{{ $fieldNameEn }}" value="{{ old($fieldNameEn, $valEn) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600">
                        {{ $sub['label'] ?? ucfirst($subKey) }} <span class="ml-1 text-gray-400">(বাংলা)</span>
                    </label>
                    <input type="text" name="{{ $fieldNameBn }}" value="{{ old($fieldNameBn, $valBn) }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                </div>
            @endif
        @endforeach
    </div>
</div>
