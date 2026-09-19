<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Marksheet') }} — {{ $student->user?->name ?? 'Student' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #1f2937; margin: 24px; }
        .header { text-align: center; border-bottom: 2px solid #1d4ed8; padding-bottom: 12px; margin-bottom: 20px; }
        .logo { max-height: 64px; margin: 0 auto 8px; }
        .school { font-size: 20px; font-weight: bold; color: #1d4ed8; }
        .sub { font-size: 12px; color: #6b7280; }
        .info { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 16px; }
        .info b { color: #374151; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        th { background: #eff6ff; text-transform: uppercase; font-size: 10px; letter-spacing: .5px; }
        .exam-title { font-size: 14px; font-weight: bold; margin: 18px 0 6px; color: #1e3a8a; }
        .summary { margin-top: 20px; font-size: 13px; }
        .summary span { margin-right: 24px; }
        .footer { margin-top: 32px; font-size: 11px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        @if($settings->logo_path)
            <img class="logo" src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo">
        @endif
        <div class="school">{{ $settings->site_name ?? config('app.name') }}</div>
        @if($settings->tagline)<div class="sub">{{ $settings->tagline }}</div>@endif
        @if($settings->address)<div class="sub">{{ $settings->address }}</div>@endif
    </div>

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
        <div class="exam-title">{{ $examName }}</div>
        <table>
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

    <div class="footer">{{ __('This is a computer-generated marksheet and does not require a signature.') }}</div>
</body>
</html>
