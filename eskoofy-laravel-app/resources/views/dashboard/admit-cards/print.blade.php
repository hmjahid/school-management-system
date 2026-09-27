<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><title>{{ __('Admit card') }} — {{ $admitCard->admit_card_number }}</title>
@include('partials.dashboard.document-style', ['documentType' => 'admit_card'])
@php
    $settings = $siteSettings ?? \App\Models\WebsiteSetting::getSettings();
    $details = $admitCard->details ?? [];
    $headerText = $details['header_text'] ?? ($settings->school_name ?? config('app.name'));
    $footerText = $details['footer_text'] ?? ($settings->full_address ?? '');
    $showLogo = $details['show_logo'] ?? true;
    $customNotes = $details['custom_notes'] ?? null;
    $documentLogoUrl = $settings?->logo_url;
    $docDesign = document_context('admit_card');
    $docTheme = $docDesign['theme'];
@endphp
<style>
    body { font-family: var(--doc-font, Arial, sans-serif); margin: 0; padding: 20px; }
    .card { max-width: 600px; margin: auto; background: var(--doc-bg, #fff); border: var(--doc-border-width, 3px) var(--doc-border-style, solid) var(--doc-border, #1e40af); border-radius: var(--doc-radius, 12px); padding: var(--doc-padding, 30px); position: relative; }
    .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 15px; margin-bottom: 15px; }
    .header img { max-height: 50px; max-width: 180px; object-fit: contain; margin-bottom: 8px; }
    .header h1 { margin: 0; font-size: var(--doc-title-size, 24px); color: var(--doc-primary, #1e40af); }
    .header h2 { margin: 5px 0 0; font-size: 18px; color: var(--doc-secondary, #1e3a8a); }
    .header .school-address { font-size: 12px; color: var(--doc-muted, #888); }
    .info { display: grid; grid-template-columns: 1fr 2fr; gap: 8px; font-size: var(--doc-base-size, 14px); }
    .info dt { font-weight: bold; color: var(--doc-muted, #555); }
    .info dd { margin: 0; color: var(--doc-text, #222); }
    .custom-notes { margin: 15px 0; padding: 10px; background: #f8fafc; border-left: 3px solid var(--doc-accent, #1e40af); font-size: 12px; color: #555; }
    .footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px dashed #ccc; font-size: 12px; color: var(--doc-muted, #888); }
    @media print { body { padding: 0; } .card { border: var(--doc-border-width, 2px) var(--doc-border-style, solid) #000; } }
</style></head>
<body>
<div class="doc-root doc-admit_card">
    <div class="doc-accent-bar"></div>
    @include('partials.dashboard.document-watermark', ['documentType' => 'admit_card'])
    <div class="card">
    @if($docTheme['show_header'])
    <div class="header">
        @if($docTheme['show_logo'] && $showLogo && $settings?->logo_url)
            <img src="{{ $settings->logo_url }}" alt="{{ $headerText }}" class="doc-logo">
        @endif
        <h1>{{ $docTheme['header_text'] ?? $headerText }}</h1>
        <h2>{{ __('Admit Card') }}</h2>
        @if($footerText)
            <div class="school-address">{{ $footerText }}</div>
        @endif
    </div>
    @endif
    <dl class="info doc-body">
        <dt>{{ __('Student Name') }}:</dt><dd class="doc-name">{{ $admitCard->student?->user?->name }}</dd>
        <dt>{{ __('Class') }}:</dt><dd>{{ $admitCard->student?->class?->name ?? 'N/A' }}</dd>
        <dt>{{ __('Section') }}:</dt><dd>{{ $admitCard->student?->section?->name ?? 'N/A' }}</dd>
        <dt>{{ __('Roll Number') }}:</dt><dd>{{ $admitCard->student?->roll_number ?? 'N/A' }}</dd>
        <dt>{{ __('Exam') }}:</dt><dd>{{ $admitCard->exam?->name }}</dd>
        @if($docTheme['show_number'])<dt>{{ __('Card Number') }}:</dt><dd class="doc-number">{{ $admitCard->admit_card_number }}</dd>@endif
        @if($docTheme['show_issue_date'])<dt>{{ __('Issue Date') }}:</dt><dd>{{ $admitCard->issue_date?->format('d M Y') }}</dd>@endif
    </dl>
    @if($docTheme['show_notes'] && $customNotes)
        <div class="custom-notes doc-notes">{{ $customNotes }}</div>
    @endif
    @if($docTheme['show_footer'])
    <div class="footer">
        <p>{{ __('This admit card is valid for the exam mentioned above.') }}</p>
        @if($footerText)
            <p>{{ $footerText }}</p>
        @endif
        @if($docTheme['show_signature'])<p>{{ __('Authorized signature') }}: ___________________</p>@endif
    </div>
    @endif
</div>
</div>
<script>
    @if(! ($preview ?? false))
        window.print();
    @endif
</script>
</body>
</html>
