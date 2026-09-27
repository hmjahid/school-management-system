<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Marksheet') }} — {{ $exam->name }}</title>
    @include('partials.dashboard.document-style', ['documentType' => 'marksheet'])
    @php
        $docDesign = document_context('marksheet');
        $docTheme = $docDesign['theme'];
        $documentLogoUrl = $settings->logo_url;
    @endphp
    {{-- dompdf supports neither var() nor calc(): every colour/size here must
         come from a literal-value class emitted by DocumentDesignService. --}}
    <style>
        body { margin: 0; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0 0 4px; }
        .header h2 { font-size: 16px; margin: 0 0 2px; font-weight: 600; }
        .header p { font-size: 12px; margin: 0; }
        .info-grid { display: flex; flex-wrap: wrap; margin: 20px 0; }
        .info-item { flex: 1 1 40%; padding: 2px 10px 2px 0; }
        .info-label { font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 14px; font-weight: 600; margin-top: 2px; }
        table.doc-table { margin: 20px 0; }
        table.doc-table th { font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .grade-box { text-align: center; padding: 20px; margin: 20px 0; }
        .grade-box .grade { font-size: 32px; font-weight: 800; }
        .grade-box .label { font-size: 12px; margin-bottom: 4px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .signatures > div { text-align: center; width: 45%; }
        .signatures .line { border-top: 1px solid; margin-top: 40px; padding-top: 8px; font-size: 12px; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; }
    </style>
</head>
<body>
<div class="doc-root doc-marksheet">
    <div class="doc-accent-bar"></div>
    @include('partials.dashboard.document-watermark', ['documentType' => 'marksheet'])
    @if($docTheme['show_header'])
    <div class="header">
        @if($docTheme['show_logo'] && $settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="" class="logo doc-logo">
        @endif
        <h1 class="doc-title">{{ $docTheme['header_text'] ?? ($settings->school_name ?? config('app.name')) }}</h1>
        @if($docTheme['show_footer'] || $settings->address)
            <p>{{ $settings->address ?? '' }}</p>
        @endif
        <h2>{{ __('Academic Transcript / Marksheet') }}</h2>
        <p>{{ $exam->name }} — {{ $exam->academicSession?->name ?? '' }}</p>
    </div>
    @endif

    <div class="info-grid doc-panel">
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Student Name') }}</div>
            <div class="info-value">{{ $result->student->user->name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Admission No.') }}</div>
            <div class="info-value">{{ $result->student->admission_number ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Class') }}</div>
            <div class="info-value">{{ $result->student->class?->name ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Roll No.') }}</div>
            <div class="info-value">{{ $result->student->roll_number ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Section') }}</div>
            <div class="info-value">{{ $result->student->section?->name ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Exam') }}</div>
            <div class="info-value">{{ $exam->name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Subject') }}</div>
            <div class="info-value">{{ $exam->subject?->name ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label doc-muted">{{ __('Exam Date') }}</div>
            <div class="info-value">{{ $exam->exam_date?->format('d M Y') ?? ($exam->start_date?->format('d M Y') ?? '—') }}</div>
        </div>
    </div>

    <table class="doc-table">
        <thead>
            <tr>
                <th>{{ __('Subject') }}</th>
                <th class="text-right">{{ __('Obtained Marks') }}</th>
                <th class="text-right">{{ __('Total Marks') }}</th>
                <th class="text-center">{{ __('Grade') }}</th>
                <th class="text-center">{{ __('Grade Point') }}</th>
                <th class="text-center">{{ __('Status') }}</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="font-bold doc-name">{{ $exam->subject?->name ?? ($exam->name ?? '—') }}</td>
                <td class="text-right font-bold">{{ $result->obtained_marks }}</td>
                <td class="text-right">{{ $exam->total_marks }}</td>
                <td class="text-center">{{ $result->grade ?? '—' }}</td>
                <td class="text-center">{{ $result->grade_point ?? '—' }}</td>
                <td class="text-center">
                    @if($result->status === 'passed')
                        <span class="doc-pass">{{ __('Passed') }}</span>
                    @elseif($result->status === 'failed')
                        <span class="doc-fail">{{ __('Failed') }}</span>
                    @else
                        {{ $result->status ?? '—' }}
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    @php
        $percentage = $exam->total_marks > 0 ? round(($result->obtained_marks / $exam->total_marks) * 100, 1) : 0;
    @endphp

    <div class="grade-box doc-panel doc-panel-muted">
        <div class="label doc-muted">{{ __('Grade Achieved') }}</div>
        <div class="grade doc-name">{{ $result->grade ?? '—' }}</div>
        <div class="doc-muted" style="margin-top:6px;font-size:13px;">
            {{ __('Grade Point') }}: {{ number_format((float)($result->grade_point ?? 0), 2) }} &middot;
            {{ __('Percentage') }}: {{ $percentage }}%
        </div>
    </div>

    @if($docTheme['show_signature'])
    <div class="signatures">
        <div>
            <div class="line doc-muted doc-signature">{{ $docTheme['signature_label'] ?? __('Class Teacher') }}</div>
        </div>
        <div>
            <div class="line doc-muted doc-signature">{{ __('Principal') }}</div>
        </div>
    </div>
    @endif

    @if($docTheme['show_footer'])
        <div class="footer">{{ $docTheme['footer_text'] ?? ($settings->address ?? '') }}</div>
    @endif
</div>
</body>
</html>
