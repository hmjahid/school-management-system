<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Testimonial') }} — {{ $testimonial->testimonial_number }}</title>
    @php
        $settings = $siteSettings ?? \App\Models\WebsiteSetting::getSettings();
        $details = $testimonial->details ?? [];
        $headerText = $details['header_text'] ?? ($settings->school_name ?? config('app.name', 'School'));
        $footerText = $details['footer_text'] ?? ($settings->full_address ?? '');
        $showLogo = $details['show_logo'] ?? true;
        $customNotes = $details['custom_notes'] ?? null;
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Georgia', serif; background: #f5f5f5; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 2rem; }
        .certificate { width: 800px; background: #fff; border: 3px double #16a34a; padding: 3rem; text-align: center; position: relative; }
        .certificate::before, .certificate::after { content: ''; position: absolute; width: 60px; height: 60px; border: 2px solid #16a34a; }
        .certificate::before { top: 10px; left: 10px; border-right: none; border-bottom: none; }
        .certificate::after { bottom: 10px; right: 10px; border-left: none; border-top: none; }
        .header { margin-bottom: 2rem; }
        .school-logo { max-height: 60px; max-width: 200px; object-fit: contain; margin-bottom: 0.5rem; }
        .school-name { font-size: 1.8rem; font-weight: bold; color: #166534; margin-bottom: 0.5rem; }
        .school-address { font-size: 0.85rem; color: #64748b; margin-bottom: 0.5rem; }
        .cert-title { font-size: 1.5rem; font-weight: bold; text-transform: uppercase; letter-spacing: 3px; color: #166534; margin: 1.5rem 0; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 0.75rem 0; }
        .cert-number { font-size: 0.9rem; color: #64748b; margin-bottom: 1.5rem; }
        .testimonial-type { font-size: 1rem; color: #16a34a; font-weight: bold; margin-bottom: 0.5rem; }
        .body { text-align: left; font-size: 1.1rem; line-height: 1.8; margin: 2rem 0; color: #334155; }
        .student-name { font-size: 1.3rem; font-weight: bold; color: #166534; text-decoration: underline; }
        .details { display: flex; justify-content: space-between; margin: 2rem 0; font-size: 0.9rem; color: #475569; }
        .rating-stars { font-size: 1.2rem; color: #eab308; margin: 1rem 0; }
        .author-info { margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-left: 3px solid #16a34a; text-align: left; font-size: 0.9rem; color: #475569; }
        .custom-notes { margin: 1.5rem 0; padding: 1rem; background: #f8fafc; border-left: 3px solid #16a34a; font-size: 0.9rem; color: #475569; text-align: left; }
        .footer { margin-top: 3rem; display: flex; justify-content: space-between; align-items: flex-end; }
        .footer-text { font-size: 0.8rem; color: #94a3b8; }
        .signature-line { border-top: 1px solid #334155; width: 200px; padding-top: 0.5rem; text-align: center; font-size: 0.85rem; color: #475569; }
        .date-line { font-size: 0.85rem; color: #64748b; }
        @media print { body { background: none; padding: 0; } .certificate { border: 3px double #000; } .certificate::before, .certificate::after { border-color: #000; } .school-name, .cert-title { color: #000; } .student-name, .testimonial-type { color: #000; } .rating-stars { color: #000; } }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            @if($showLogo && $settings?->logo_url)
                <img src="{{ $settings->logo_url }}" alt="{{ $headerText }}" class="school-logo">
            @endif
            <div class="school-name">{{ $headerText }}</div>
            @if($footerText)
                <div class="school-address">{{ $footerText }}</div>
            @endif
        </div>

        <div class="testimonial-type">{{ __('Student Testimonial') }}</div>
        <div class="cert-number">{{ __('No') }}: {{ $testimonial->testimonial_number }}</div>

        <div class="cert-title">{{ $testimonial->name }}</div>

        <div class="body">
            @if(!empty($testimonial->body) && is_array($testimonial->body))
                @foreach($testimonial->body as $line)
                    <p>{{ $line }}</p>
                @endforeach
            @elseif(!empty($testimonial->body))
                <p>{{ $testimonial->body }}</p>
            @else
                <p>{{ __('This testimonial is awarded to') }} <span class="student-name">{{ $testimonial->student?->user?->name ?? '________' }}</span>
                {{ __('in recognition of their outstanding') }} {{ __(ucfirst(str_replace('_', ' ', $testimonial->testimonial_type ?? ''))) }} {{ __('at our institution.') }}</p>
            @endif
        </div>

        @if($testimonial->rating)
            <div class="rating-stars">{{ str_repeat('★', $testimonial->rating) }}{{ str_repeat('☆', 5 - $testimonial->rating) }}</div>
        @endif

        @if($testimonial->author_name)
            <div class="author-info">
                <strong>{{ __('Awarded by') }}:</strong> {{ $testimonial->author_name }}{{ $testimonial->author_designation ? ', ' . $testimonial->author_designation : '' }}
            </div>
        @endif

        @if($customNotes)
            <div class="custom-notes">{{ $customNotes }}</div>
        @endif

        <div class="details">
            <div>{{ __('Student') }}: {{ $testimonial->student?->user?->name ?? 'N/A' }}</div>
            <div>{{ __('Class') }}: {{ $testimonial->student?->class?->name ?? 'N/A' }} {{ $testimonial->student?->section?->name ? '(' . $testimonial->student->section->name . ')' : '' }}</div>
        </div>

        <div class="footer">
            <div>
                <div class="date-line">
                    {{ __('Date') }}: {{ $testimonial->issue_date?->format('d M Y') }}
                </div>
                @if($footerText)
                    <div class="footer-text">{{ $footerText }}</div>
                @endif
            </div>
            <div class="signature-line">
                {{ __('Authorized Signature') }}
            </div>
        </div>
    </div>
    <script>window.onload = function() { window.print(); };</script>
</body>
</html>
