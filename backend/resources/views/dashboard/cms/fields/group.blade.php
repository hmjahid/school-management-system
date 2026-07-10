{{--
    Group field partial — renders a flat list of sub-fields (text or textarea)
    in a 2-column EN/BN grid. Used for nav labels, footer text, and other
    grouped UI copy.

    Variables:
      - $name: base field name (e.g. "nav")
      - $field: section definition with 'fields' array
      - $dataEn, $dataBn: existing EN/BN values keyed by sub-key
--}}
<div class="space-y-5">
    @foreach (($field['fields'] ?? []) as $sub)
        @php
            $subKey = $sub['key'];
            $subType = $sub['type'] ?? 'text';
            $subName = $name.'_'.str_replace('.', '_', $subKey);
            $subLabel = $sub['label'] ?? ucfirst(str_replace('_', ' ', $subKey));
            $subRows = $sub['rows'] ?? null;
            $valEn = old($subName.'_en', data_get($dataEn, $subKey, ''));
            $valBn = old($subName.'_bn', data_get($dataBn, $subKey, ''));
        @endphp
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label for="cms-{{ $subName }}-en" class="mb-1 block text-xs font-medium text-gray-600">
                    {{ $subLabel }} <span class="text-gray-400">(EN)</span>
                </label>
                @if ($subType === 'textarea')
                    <textarea id="cms-{{ $subName }}-en" name="{{ $subName }}_en" rows="{{ $subRows ?? 2 }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ $valEn }}</textarea>
                @else
                    <input type="text" id="cms-{{ $subName }}-en" name="{{ $subName }}_en" value="{{ $valEn }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @endif
            </div>
            <div>
                <label for="cms-{{ $subName }}-bn" class="mb-1 block text-xs font-medium text-gray-600">
                    {{ $subLabel }} <span class="text-gray-400">(বাংলা)</span>
                </label>
                @if ($subType === 'textarea')
                    <textarea id="cms-{{ $subName }}-bn" name="{{ $subName }}_bn" rows="{{ $subRows ?? 2 }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">{{ $valBn }}</textarea>
                @else
                    <input type="text" id="cms-{{ $subName }}-bn" name="{{ $subName }}_bn" value="{{ $valBn }}"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                @endif
            </div>
        </div>
    @endforeach
</div>
