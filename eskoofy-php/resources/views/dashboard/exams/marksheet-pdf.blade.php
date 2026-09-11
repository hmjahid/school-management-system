<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Marksheet') }} — {{ $exam->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 13px; color: #1f2937; margin: 40px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; }
        .header h2 { font-size: 16px; color: #6b7280; margin: 0 0 2px; font-weight: 600; }
        .header p { font-size: 12px; color: #9ca3af; margin: 0; }
        .header .logo { max-height: 60px; margin-bottom: 8px; }
        .info-grid { display: flex; flex-wrap: wrap; gap: 6px 20px; margin: 20px 0; padding: 16px; background: #f9fafb; border-radius: 8px; }
        .info-item { flex: 1 1 40%; }
        .info-label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
        .info-value { font-size: 14px; font-weight: 600; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #d1d5db; padding: 10px 12px; text-align: left; }
        th { background: #f3f4f6; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #374151; }
        td { font-size: 13px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .grade-box { text-align: center; padding: 20px; background: #f0fdf4; border: 2px solid #86efac; border-radius: 12px; margin: 20px 0; }
        .grade-box .grade { font-size: 32px; font-weight: 800; color: #16a34a; }
        .grade-box .label { font-size: 12px; color: #6b7280; margin-bottom: 4px; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .signatures > div { text-align: center; width: 45%; }
        .signatures .line { border-top: 1px solid #d1d5db; margin-top: 40px; padding-top: 8px; font-size: 12px; color: #6b7280; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="header">
        @if($settings->logo_url)
            <img src="{{ $settings->logo_url }}" alt="" class="logo">
        @endif
        <h1>{{ $settings->school_name ?? config('app.name') }}</h1>
        <p>{{ $settings->address ?? '' }}</p>
        <h2>{{ __('Academic Transcript / Marksheet') }}</h2>
        <p>{{ $exam->name }} — {{ $exam->academicSession?->name ?? '' }}</p>
    </div>

    <div class="info-grid">
        <div class="info-item">
            <div class="info-label">{{ __('Student Name') }}</div>
            <div class="info-value">{{ $result->student->user->name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('Admission No.') }}</div>
            <div class="info-value">{{ $result->student->admission_number ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('Class') }}</div>
            <div class="info-value">{{ $result->student->class?->name ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('Roll No.') }}</div>
            <div class="info-value">{{ $result->student->roll_number ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('Section') }}</div>
            <div class="info-value">{{ $result->student->section?->name ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('Exam') }}</div>
            <div class="info-value">{{ $exam->name }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('Subject') }}</div>
            <div class="info-value">{{ $exam->subject?->name ?? '—' }}</div>
        </div>
        <div class="info-item">
            <div class="info-label">{{ __('Exam Date') }}</div>
            <div class="info-value">{{ $exam->exam_date?->format('d M Y') ?? ($exam->start_date?->format('d M Y') ?? '—') }}</div>
        </div>
    </div>

    <table>
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
                <td class="font-bold">{{ $exam->subject?->name ?? ($exam->name ?? '—') }}</td>
                <td class="text-right font-bold">{{ $result->obtained_marks }}</td>
                <td class="text-right">{{ $exam->total_marks }}</td>
                <td class="text-center">{{ $result->grade ?? '—' }}</td>
                <td class="text-center">{{ $result->grade_point ?? '—' }}</td>
                <td class="text-center">
                    @if($result->status === 'passed')
                        <span style="color:#16a34a;font-weight:700;">{{ __('Passed') }}</span>
                    @elseif($result->status === 'failed')
                        <span style="color:#dc2626;font-weight:700;">{{ __('Failed') }}</span>
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

    <div class="grade-box">
        <div class="label">{{ __('Grade Achieved') }}</div>
        <div class="grade">{{ $result->grade ?? '—' }}</div>
        <div style="margin-top:6px;font-size:13px;color:#6b7280;">
            {{ __('Grade Point') }}: {{ number_format((float)($result->grade_point ?? 0), 2) }} &middot;
            {{ __('Percentage') }}: {{ $percentage }}%
        </div>
    </div>

    <div class="signatures">
        <div>
            <div class="line">{{ __('Class Teacher') }}</div>
        </div>
        <div>
            <div class="line">{{ __('Principal') }}</div>
        </div>
    </div>

    <div class="footer">{{ __('Generated on') }} {{ now()->format('d M Y, h:i A') }}</div>
</body>
</html>
