{{--
    Hero field: groups headline / motto / background image together.
    Variables: $name, $field, $dataEn, $dataBn
--}}
@php
    $dataEn = $dataEn ?? [];
    $dataBn = $dataBn ?? [];
    $fields = $field['fields'] ?? [];
@endphp
<div class="space-y-3 rounded-lg border border-amber-200 bg-amber-50/40 p-4">
    @foreach ($fields as $sub)
        @php
            $subName = $name.'_'.str_replace('.', '_', $sub['key']);
            $valEn = $dataEn[$sub['key']] ?? '';
            $valBn = $dataBn[$sub['key']] ?? '';
        @endphp
        @if(($sub['type'] ?? 'text') === 'image')
            @include('dashboard.cms.fields.image', [
                'name' => $subName,
                'field' => $sub,
                'valueEn' => $valEn,
                'valueBn' => $valBn,
                'value' => $valEn,
            ])
        @else
            @include('dashboard.cms.fields._pair-text', [
                'name' => $subName,
                'field' => $sub,
                'valueEn' => old($subName.'_en', $valEn),
                'valueBn' => old($subName.'_bn', $valBn),
            ])
        @endif
    @endforeach
</div>
