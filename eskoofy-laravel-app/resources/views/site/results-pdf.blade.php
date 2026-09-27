<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Marksheet') }} — {{ $student->user?->name ?? 'Student' }}</title>
    @include('partials.dashboard.document-style', ['documentType' => 'marksheet'])
    @php
        $docDesign = document_context('marksheet');
        $docTheme = $docDesign['theme'];
    @endphp
    {{-- dompdf supports neither var() nor calc(): every colour/size here must
         come from a literal-value class emitted by DocumentDesignService. --}}
    <style>
        body { margin: 0; }
        .header { text-align: center; border-bottom: 2px solid; padding-bottom: 12px; margin-bottom: 20px; }
        .logo { max-height: 64px; margin: 0 auto 8px; }
        .school { font-weight: bold; }
        .sub { font-size: 12px; }
        .info { display: flex; justify-content: space-between; margin-bottom: 16px; }
        .info b { font-weight: 700; }
        table.doc-table { font-size: 12px; }
        table.doc-table th, table.doc-table td { padding: 6px 8px; text-align: left; }
        table.doc-table th { text-transform: uppercase; font-size: 10px; letter-spacing: .5px; }
        .exam-title { font-size: 14px; font-weight: bold; margin: 18px 0 6px; }
        .summary { margin-top: 20px; font-size: 13px; }
        .summary span { margin-right: 24px; }
        .footer { margin-top: 32px; font-size: 11px; text-align: center; }
    </style>
</head>
<body>
<div class="doc-root doc-marksheet">
    <div class="doc-accent-bar"></div>
    @include('partials.dashboard.document-watermark', ['documentType' => 'marksheet'])
    @if($docTheme['show_header'])
    <div class="header">
        @if($docTheme['show_logo'] && $settings->logo_path)
            <img class="logo doc-logo" src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo">
        @endif
        <div class="school doc-title">{{ $docTheme['header_text'] ?? ($settings->site_name ?? config('app.name')) }}</div>
        @if($settings->tagline)<div class="sub doc-muted">{{ $settings->tagline }}</div>@endif
        @if($docTheme['show_footer'] && $settings->address)<div class="sub doc-muted">{{ $settings->address }}</div>@endif
    </div>
    @endif

    <div class="info">
        <div>
            <div><b>{{ __('Student') }}:</b> {{ $student->user?->name ?? '—' }}</div>
            <div><b>{{ __('Class') }}:</b> {{ $student->class?->name ?? '—' }}</div>
            <div><b>{{ __('Section') }}:</b> {{ $student->section?->name ?? '—' }}</div>
        </div>
        <div style="text-align: right;">
            <div><b>{{ __('Roll') }}:</b> {{ $student->roll_number ?? $student->roll_no ?? '—' }}</div>
            <div><b>{{ __('Admission No') }}:</b> {{ $student->admission_number ?? '—' }}</div>
            <div><b>{{ __('Session') }}:</b> {{ request('academic_session_id') }}</div>
        </div>
    </div>

    @php
        $grouped = $result->groupBy(fn($r) => $r->exam?->name ?: __('Exam'));
        $totalObtained = $result->pluck('obtained_marks')->filter()->sum();
        $totalMax = $result->sum(fn($r) => $r->exam?->total_marks ?? 0);
        $percentage = $totalMax > 0 ? round(($totalObtained / $totalMax) * 100, 1) : 0;
        $grade = $percentage >= 80 ? 'A+' : ($percentage >= 70 ? 'A' : ($percentage >= 60 ? 'A-' : ($percentage >= 50 ? 'B' : ($percentage >= 40 ? 'C' : 'F'))));
    @endphp

    @foreach($grouped as $examName => $examResults)
        <div class="exam-title doc-name">{{ $examName }}</div>
        <table class="doc-table">
            <thead>
                <tr>
                    <th>{{ __('Subject') }}</th>
                    <th>{{ __('Marks') }}</th>
                    <th>{{ __('Grade') }}</th>
                    <th>{{ __('Remarks') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examResults as $r)
                    <tr>
                        <td>{{ $r->subject?->name ?? '—' }}</td>
                        <td>{{ $r->obtained_marks ?? '—' }} / {{ $r->exam?->total_marks ?? '—' }}</td>
                        <td>{{ $r->grade ?? '—' }}</td>
                        <td>{{ $r->remarks ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="summary">
        <span><b>{{ __('Total') }}:</b> {{ $totalObtained }} / {{ $totalMax }}</span>
        <span><b>{{ __('Percentage') }}:</b> {{ $percentage }}%</span>
        <span><b>{{ __('Grade') }}:</b> {{ $grade }}</span>
    </div>

    @if($docTheme['show_footer'])
        <div class="footer doc-muted">{{ $docTheme['footer_text'] ?? __('This is a computer-generated marksheet and does not require a signature.') }}</div>
    @endif
</div>
</body>
</html>
