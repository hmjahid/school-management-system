{{--
    Document design stylesheet.

    Injects the active design's CSS custom properties, the selected template's
    layout rules, the watermark layer rules and the design's sanitised custom
    CSS into every exportable document (certificate, testimonial, marksheet,
    admit card, student ID card) — screen preview, browser print and PDF alike.

    Kept byte-identical with eskoofy-php-app/resources/views/partials/dashboard/document-style.blade.php
    (see eskoofy-php-app/AGENTS.md) and mirrored by esc_document_design_css() in
    the WordPress theme and documentDesignCss() in the Node variant.
--}}
@php($documentContext = document_context($documentType ?? 'certificate'))
<style>
/* Document design: {{ $documentContext['type'] }} — template "{{ $documentContext['theme']['template'] }}" */
{!! $documentContext['css'] !!}
</style>
