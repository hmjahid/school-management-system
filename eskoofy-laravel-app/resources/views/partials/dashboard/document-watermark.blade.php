{{--
    Institution watermark layer for an exportable document.

    Renders nothing at all when the watermark is disabled for this document
    type, so a disabled watermark leaves no empty container in the DOM or the
    PDF. The visual styling (opacity, rotation, position) comes from
    partials.dashboard.document-style, which must be included in the same
    document.

    Kept byte-identical with eskoofy-php-app/resources/views/partials/dashboard/document-watermark.blade.php
    (see eskoofy-php-app/AGENTS.md) and mirrored by esk_document_watermark_html()
    in the WordPress theme and DocumentWatermark in the Node variant.
--}}
@php($documentWatermark = document_watermark($documentType ?? 'certificate'))
@if($documentWatermark['enabled'])
    <div class="doc-watermark" aria-hidden="true">
        @if($documentWatermark['type'] === 'text')
            <div class="doc-watermark-item">{{ $documentWatermark['text'] }}</div>
        @elseif(! empty($documentWatermark['image_path']))
            <div class="doc-watermark-item"><img src="{{ $documentWatermark['image_path'] }}" alt=""></div>
        @endif
    </div>
@endif
