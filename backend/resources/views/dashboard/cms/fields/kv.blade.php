{{--
    Key/value field partial: a small object with named sub-fields.
    Variables: $name, $field, $dataEn, $dataBn
    $field['fields'] = [['key'=>'name','label'=>'Name','type'=>'text'], ...]
--}}
@php
    $dataEn = $dataEn ?? [];
    $dataBn = $dataBn ?? [];
    $fields = $field['fields'] ?? [];
@endphp
<div class="space-y-3">
    @foreach ($fields as $sub)
        @php
            // Form names use underscores; the controller converts back.
            $subName = $name.'_'.str_replace('.', '_', $sub['key']);
            $valEn = $dataEn[$sub['key']] ?? '';
            $valBn = $dataBn[$sub['key']] ?? '';
        @endphp
        @if(($sub['type'] ?? 'text') === 'textarea')
            @include('dashboard.cms.fields._pair-textarea', [
                'name' => $subName,
                'field' => $sub,
                'valueEn' => old($subName.'_en', $valEn),
                'valueBn' => old($subName.'_bn', $valBn),
                'rows' => $sub['rows'] ?? 4,
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
