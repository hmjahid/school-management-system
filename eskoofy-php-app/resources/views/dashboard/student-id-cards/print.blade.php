<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><title>{{ __('ID card') }} — {{ $studentIdCard->id_card_number }}</title>
@include('partials.dashboard.document-style', ['documentType' => 'id_card'])
@php
    $settings = $siteSettings ?? \App\Models\WebsiteSetting::getSettings();
    $details = $studentIdCard->details ?? [];
    $headerText = $details['header_text'] ?? ($settings->school_name ?? config('app.name'));
    $footerText = $details['footer_text'] ?? ($settings->full_address ?? '');
    $showLogo = $details['show_logo'] ?? true;
    $customNotes = $details['custom_notes'] ?? null;
    $documentLogoUrl = $settings?->logo_url;
    $docDesign = document_context('id_card');
    $docTheme = $docDesign['theme'];
@endphp
<style>
    body { font-family: var(--doc-font, Arial, sans-serif); margin: 0; padding: 20px; display: flex; justify-content: center; }
    .card { width: 320px; background: var(--doc-bg, #fff); border: var(--doc-border-width, 3px) var(--doc-border-style, solid) var(--doc-border, #1e40af); border-radius: var(--doc-radius, 12px); padding: var(--doc-padding, 20px); text-align: center; position: relative; }
    .header { border-bottom: 2px dashed #ccc; padding-bottom: 10px; margin-bottom: 10px; }
    .header img { max-height: 40px; max-width: 120px; object-fit: contain; margin-bottom: 6px; }
    .header h1 { margin: 0; font-size: var(--doc-title-size, 18px); color: var(--doc-primary, #1e40af); }
    .school-address { margin: 2px 0; font-size: 11px; color: var(--doc-muted, #888); }
    .photo { width: 80px; height: 80px; border-radius: 50%; background: #e5e7eb; margin: 10px auto; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: bold; color: var(--doc-primary, #1e40af); }
    .info { text-align: left; font-size: var(--doc-base-size, 13px); }
    .info p { margin: 4px 0; }
    .info strong { display: inline-block; width: 90px; color: var(--doc-muted, #555); }
    .custom-notes { margin-top: 8px; padding: 8px; background: #f8fafc; border-left: 3px solid var(--doc-accent, #1e40af); font-size: 11px; color: #555; text-align: left; }
    .footer { margin-top: 10px; padding-top: 10px; border-top: 2px dashed #ccc; font-size: 11px; color: var(--doc-muted, #888); }
    @media print { body { padding: 0; } }
</style></head>
<body>
<div class="doc-root doc-id_card">
    <div class="doc-accent-bar"></div>
    @include('partials.dashboard.document-watermark', ['documentType' => 'id_card'])
    <div class="card">
    @if($docTheme['show_header'])
    <div class="header">
        @if($docTheme['show_logo'] && $showLogo && $settings?->logo_url)
            <img src="{{ $settings->logo_url }}" alt="{{ $headerText }}" class="doc-logo">
        @endif
        <h1>{{ $docTheme['header_text'] ?? $headerText }}</h1>
        <p style="margin:2px 0;font-size:12px;color:var(--doc-muted, #666);">{{ __('Student Identity Card') }}</p>
        @if($footerText)
            <div class="school-address">{{ $footerText }}</div>
        @endif
    </div>
    @endif
    @php $name = $studentIdCard->student?->user?->name ?? 'Student'; $initials = implode('', array_map(fn($w) => strtoupper(substr($w,0,1)), explode(' ', $name))); @endphp
    <div class="photo">{{ $studentIdCard->photo_url ? '<img src="'.$studentIdCard->photo_url.'" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">' : $initials }}</div>
    <div class="info doc-body">
        <p><strong>{{ __('Name') }}:</strong> <span class="doc-name">{{ $name }}</span></p>
        @if($docTheme['show_number'])<p><strong>{{ __('ID No') }}:</strong> <span class="doc-number">{{ $studentIdCard->id_card_number }}</span></p>@endif
        <p><strong>{{ __('Class') }}:</strong> {{ $studentIdCard->student?->class?->name ?? 'N/A' }}</p>
        <p><strong>{{ __('Section') }}:</strong> {{ $studentIdCard->student?->section?->name ?? 'N/A' }}</p>
        <p><strong>{{ __('Roll') }}:</strong> {{ $studentIdCard->student?->roll_number ?? 'N/A' }}</p>
        @if($studentIdCard->blood_group)<p><strong>{{ __('Blood') }}:</strong> {{ $studentIdCard->blood_group }}</p>@endif
    </div>
    @if($docTheme['show_notes'] && $customNotes)
        <div class="custom-notes doc-notes">{{ $customNotes }}</div>
    @endif
    @if($docTheme['show_footer'] || $docTheme['show_issue_date'])
    <div class="footer">
        <p>{{ __('Issue date') }}: {{ $studentIdCard->issue_date?->format('d M Y') }}
        @if($studentIdCard->expiry_date) | {{ __('Expires') }}: {{ $studentIdCard->expiry_date->format('d M Y') }}@endif
        </p>
        @if($footerText)
            <p>{{ $footerText }}</p>
        @endif
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
