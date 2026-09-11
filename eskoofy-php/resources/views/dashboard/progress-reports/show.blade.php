<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Progress Report') }} — {{ $student->user?->name ?? 'Student' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1f2937; background: #f5f5f5; padding: 2rem; }
        .report { max-width: 800px; margin: 0 auto; background: #fff; padding: 2.5rem; border: 1px solid #e5e7eb; }
        .header { display: flex; align-items: center; gap: 1rem; border-bottom: 2px solid #2563eb; padding-bottom: 1rem; margin-bottom: 1.5rem; }
        .school-logo { max-height: 60px; max-width: 160px; object-fit: contain; }
        .school-name { font-size: 1.5rem; font-weight: bold; color: #1e40af; }
        .school-address { font-size: 0.8rem; color: #6b7280; }
        .report-title { text-align: center; font-size: 1.25rem; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 1.5rem; color: #111827; }
        .student-info { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.5rem 2rem; font-size: 0.9rem; margin-bottom: 1.5rem; }
        .student-info div { padding: 0.25rem 0; }
        .student-info span { font-weight: 600; color: #374151; }
        .section-title { font-size: 1rem; font-weight: bold; color: #1e40af; margin: 1.5rem 0 0.75rem; border-left: 4px solid #2563eb; padding-left: 0.5rem; }
        table { width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-bottom: 0.5rem; }
        th, td { border: 1px solid #d1d5db; padding: 0.5rem 0.6rem; text-align: left; }
        th { background: #eff6ff; font-weight: 600; color: #1e3a8a; }
        .summary { display: flex; flex-wrap: wrap; gap: 1.5rem; margin-top: 1rem; font-size: 0.95rem; }
        .summary div { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 0.5rem; padding: 0.75rem 1rem; }
        .summary strong { display: block; font-size: 1.1rem; color: #1e40af; }
        .footer { margin-top: 2rem; display: flex; justify-content: space-between; align-items: flex-end; font-size: 0.8rem; color: #6b7280; }
        .signature-line { border-top: 1px solid #334155; width: 200px; padding-top: 0.5rem; text-align: center; }
        @media print { body { background: none; padding: 0; } .report { border: none; } }
    </style>
</head>
<body>
    <div class="report">
        <div class="header">
            @if($settings?->logo_url)
                <img src="{{ $settings->logo_url }}" alt="{{ $settings->school_name ?? config('app.name') }}" class="school-logo">
            @endif
            <div>
                <div class="school-name">{{ $settings->school_name ?? config('app.name') }}</div>
                @if($settings?->full_address)
                    <div class="school-address">{{ $settings->full_address }}</div>
                @endif
            </div>
        </div>

        <div class="report-title">{{ __('Student Progress Report') }}</div>

        <div class="student-info">
            <div><span>{{ __('Name') }}:</span> {{ $student->user?->name ?? 'N/A' }}</div>
            <div><span>{{ __('Admission No') }}:</span> {{ $student->admission_number ?? $student->admission_no ?? 'N/A' }}</div>
            <div><span>{{ __('Class') }}:</span> {{ $student->class?->name ?? 'N/A' }}</div>
            <div><span>{{ __('Section') }}:</span> {{ $student->section?->name ?? 'N/A' }}</div>
            <div><span>{{ __('Batch') }}:</span> {{ $student->batch?->name ?? 'N/A' }}</div>
            <div><span>{{ __('Roll Number') }}:</span> {{ $student->roll_number ?? 'N/A' }}</div>
        </div>

        <div class="section-title">{{ __('Examination Results') }}</div>
        @if(count($rows) > 0)
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Exam') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Obtained') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Percentage') }}</th>
                        <th>{{ __('Grade') }}</th>
                        <th>{{ __('Remark') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td>{{ $row['exam_name'] }}</td>
                            <td>{{ $row['subject'] }}</td>
                            <td>{{ $row['obtained'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td>{{ $row['percentage'] }}%</td>
                            <td>{{ $row['grade'] }}</td>
                            <td>{{ $row['remark'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-size: 0.9rem; color: #6b7280;">{{ __('No exam results available.') }}</p>
        @endif

        <div class="section-title">{{ __('Assignment Submissions') }}</div>
        @if($assignmentAverage !== null)
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Assignment') }}</th>
                        <th>{{ __('Subject') }}</th>
                        <th>{{ __('Marks') }}</th>
                        <th>{{ __('Total') }}</th>
                        <th>{{ __('Percentage') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignmentRows as $row)
                        <tr>
                            <td>{{ $row['title'] }}</td>
                            <td>{{ $row['subject'] }}</td>
                            <td>{{ $row['marks'] }}</td>
                            <td>{{ $row['total'] }}</td>
                            <td>{{ $row['percentage'] }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="font-size: 0.9rem; color: #6b7280;">{{ __('No assignment submissions available.') }}</p>
        @endif

        <div class="summary">
            <div>
                {{ __('Overall Percentage') }}
                <strong>{{ $overallPercentage }}%</strong>
            </div>
            <div>
                {{ __('Overall Grade') }}
                <strong>{{ $overall['grade'] }} ({{ $overall['points'] }})</strong>
            </div>
            <div>
                {{ __('Assignment Average') }}
                <strong>{{ $assignmentAverage !== null ? $assignmentAverage . '%' : 'N/A' }}</strong>
            </div>
        </div>

        <div class="footer">
            <div>
                {{ __('Generated') }}: {{ $generatedAt->format('d M Y h:i A') }}
            </div>
            <div class="signature-line">{{ __('Authorized Signature') }}</div>
        </div>
    </div>
    <script>window.onload = function() { if (window.location.search.indexOf('view=1') === -1) { window.print(); } };</script>
</body>
</html>
