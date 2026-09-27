<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Certificate') }} — {{ $certificate->certificate_number }}</title>
    @include('partials.dashboard.document-style', ['documentType' => 'certificate'])
    @php
        $settings = $siteSettings ?? \App\Models\WebsiteSetting::getSettings();
        $details = $certificate->details ?? [];
        $headerText = $details['header_text'] ?? ($settings->school_name ?? config('app.name', 'School'));
        $footerText = $details['footer_text'] ?? ($settings->full_address ?? '');
        $showLogo = $details['show_logo'] ?? true;
        $customNotes = $details['custom_notes'] ?? null;
        $documentLogoUrl = $settings?->logo_url;
        $docDesign = document_context('certificate');
        $docTheme = $docDesign['theme'];
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: var(--doc-font, 'Georgia', serif); background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .doc-root { width: 800px; }
        .certificate { background: var(--doc-bg, #fff); border: var(--doc-border-width, 3px) var(--doc-border-style, double) var(--doc-border, #2563eb); padding: var(--doc-padding, 3rem); text-align: center; position: relative; }
        .certificate::before, .certificate::after { content: ''; position: absolute; width: 60px; height: 60px; border: 2px solid var(--doc-border, #2563eb); }
        .certificate::before { top: 10px; left: 10px; border-right: none; border-bottom: none; }
        .certificate::after { bottom: 10px; right: 10px; border-left: none; border-top: none; }
        .header { margin-bottom: 2rem; }
        .school-logo { max-height: 60px; max-width: 200px; object-fit: contain; margin-bottom: 0.5rem; }
        .school-name { font-size: 1.8rem; font-weight: bold; color: var(--doc-primary, #1e40af); margin-bottom: 0.5rem; }
        .school-address { font-size: 0.85rem; color: var(--doc-muted, #64748b); margin-bottom: 0.5rem; }
        .cert-title { font-size: var(--doc-title-size, 1.5rem); font-weight: bold; text-transform: uppercase; letter-spacing: 3px; color: var(--doc-secondary, #1e3a8a); margin: 1.5rem 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 0.75rem 0; }
        .cert-number { font-size: 0.9rem; color: var(--doc-muted, #64748b); margin-bottom: 1.5rem; }
        .body { text-align: left; font-size: var(--doc-base-size, 1.1rem); line-height: 1.8; margin: 2rem 0; color: var(--doc-text, #334155); }
        .student-name { font-size: 1.3rem; font-weight: bold; color: var(--doc-primary, #1e40af); text-decoration: underline; }
        .details { display: flex; justify-content: space-between; margin: 2rem 0; font-size: 0.9rem; color: #475569; }
        .custom-notes { margin: 1.5rem 0; padding: 1rem; background: #f8fafc; border-left: 3px solid var(--doc-accent, #2563eb); font-size: 0.9rem; color: #475569; text-align: left; }
        .footer { margin-top: 3rem; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-text { font-size: 0.8rem; color: #94a3b8; }
        .signature-line { border-top: 1px solid #334155; width: 200px; padding-top: 0.5rem; text-align: center; font-size: 0.85rem; color: #475569; }
        .date-line { font-size: 0.85rem; color: #64748b; }
        @media print { body { background: none; padding: 0; } .certificate { border: var(--doc-border-width, 3px) var(--doc-border-style, double) #000; } .certificate::before, .certificate::after { border-color: #000; } .school-name, .cert-title { color: #000; } .student-name { color: #000; } }
    </style>
</head>
<body>
    <div class="doc-root doc-certificate">
        <div class="doc-accent-bar"></div>
        @include('partials.dashboard.document-watermark', ['documentType' => 'certificate'])
        <div class="certificate">
            @if($docTheme['show_header'])
                <div class="header">
                    @if($docTheme['show_logo'] && $showLogo && $settings?->logo_url)
                        <img src="{{ $settings->logo_url }}" alt="{{ $headerText }}" class="school-logo doc-logo">
                    @endif
                    <div class="school-name">{{ $docTheme['header_text'] ?? $headerText }}</div>
                    @if($footerText)
                        <div class="school-address doc-muted">{{ $footerText }}</div>
                    @endif
                    <p style="font-size: 0.9rem; color: var(--doc-muted, #64748b);">{{ __('Certificate of') }} {{ __(ucfirst($certificate->certificate_type)) }}</p>
                </div>
            @endif

            @if($docTheme['show_number'])
                <div class="cert-number doc-number">{{ __('Certificate No') }}: {{ $certificate->certificate_number }}</div>
            @endif

            <div class="cert-title doc-title">{{ __(ucfirst($certificate->certificate_type)) }} {{ __('Certificate') }}</div>

            <div class="body doc-body">
                @if(!empty($certificate->body) && is_array($certificate->body))
                    @foreach($certificate->body as $line)
                        <p>{{ $line }}</p>
                    @endforeach
                @elseif(!empty($certificate->body))
                    <p>{{ $certificate->body }}</p>
                @else
                    <p>{{ __('This is to certify that') }} <span class="student-name doc-name">{{ $certificate->student?->user?->name ?? '________' }}</span>
                    {{ __('has successfully completed') }} {{ __(ucfirst($certificate->certificate_type)) }} {{ __('at our institution.') }}</p>
                @endif
            </div>

            @if($docTheme['show_notes'] && $customNotes)
                <div class="custom-notes doc-notes">{{ $customNotes }}</div>
            @endif

            <div class="details">
                <div>{{ __('Student') }}: {{ $certificate->student?->user?->name ?? 'N/A' }}</div>
                <div>{{ __('Class') }}: {{ $certificate->student?->class?->name ?? 'N/A' }} {{ $certificate->student?->section?->name ? '(' . $certificate->student->section->name . ')' : '' }}</div>
            </div>

            <div class="footer">
                <div>
                    @if($docTheme['show_issue_date'])
                        <div class="date-line">
                            {{ __('Date') }}: {{ $certificate->issue_date?->format('d M Y') }}
                        </div>
                    @endif
                    @if($docTheme['show_footer'] && $footerText)
                        <div class="footer-text doc-muted">{{ $footerText }}</div>
                    @endif
                </div>
                @if($docTheme['show_signature'])
                    <div class="signature-line doc-signature">
                        {{ $docTheme['signature_label'] ?? __('Authorized Signature') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
