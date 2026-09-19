<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Seat plan') }} — {{ $exam->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; color: #111; }
        .school-header { text-align: center; border-bottom: 3px solid #1e40af; padding-bottom: 12px; margin-bottom: 18px; }
        .school-header img { max-height: 56px; max-width: 200px; object-fit: contain; margin-bottom: 8px; }
        .school-header h1 { margin: 0; font-size: 24px; color: #1e40af; }
        .school-header .tagline { font-size: 13px; color: #666; }
        .school-header .address { font-size: 12px; color: #888; }
        .exam-meta { text-align: center; margin-bottom: 22px; font-size: 15px; }
        .exam-meta strong { color: #1e40af; }
        .room { page-break-inside: avoid; border: 2px solid #000; border-radius: 8px; padding: 14px; margin-bottom: 20px; }
        .room-title { font-size: 17px; font-weight: bold; text-align: center; border-bottom: 1px dashed #999; padding-bottom: 8px; margin-bottom: 12px; }
        table.seat-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        table.seat-table th, table.seat-table td { border: 1px solid #444; padding: 6px 8px; text-align: left; }
        table.seat-table th { background: #f1f5f9; }
        .signature { margin-top: 30px; display: flex; justify-content: space-between; font-size: 13px; }
        @media print { body { padding: 0; } .preview-controls { display: none; } }
    </style>
</head>
<body>
    @if($preview ?? false)
        <div class="preview-controls" style="margin-bottom:16px;">
            <form method="get" action="">
                <label>Students per room:
                    <input type="number" name="per_room" value="{{ $perRoom }}" min="1" style="width:80px;">
                </label>
                <button type="submit" style="padding:4px 10px;">{{ __('Apply') }}</button>
                <a href="{{ route('dashboard.seat-plans.generate', $exam) }}" style="margin-left:10px;">{{ __('Download PDF') }}</a>
            </form>
        </div>
    @endif

    <div class="school-header">
        @if($settings?->logo_url)
            <img src="{{ $settings->logo_url }}" alt="{{ $settings->school_name }}">
        @endif
        <h1>{{ $settings->school_name ?? config('app.name') }}</h1>
        @if($settings->tagline ?? null)
            <div class="tagline">{{ $settings->tagline }}</div>
        @endif
        @if($settings->full_address ?? null)
            <div class="address">{{ $settings->full_address }}</div>
        @endif
    </div>

    <div class="exam-meta">
        <div><strong>{{ __('Exam') }}:</strong> {{ $exam->name }}</div>
        <div><strong>{{ __('Date') }}:</strong> {{ $date }} &nbsp; | &nbsp; <strong>{{ __('Batch') }}:</strong> {{ $exam->batch?->name ?? 'N/A' }} &nbsp; | &nbsp; <strong>{{ __('Section') }}:</strong> {{ $exam->section?->name ?? 'N/A' }}</div>
        <div><strong>{{ __('Students per room') }}:</strong> {{ $perRoom }}</div>
    </div>

    @forelse($rooms as $roomName => $roomStudents)
        <div class="room">
            <div class="room-title">{{ $roomName }}</div>
            <table class="seat-table">
                <thead>
                    <tr>
                        <th style="width:12%;">{{ __('Seat') }}</th>
                        <th style="width:18%;">{{ __('Roll') }}</th>
                        <th>{{ __('Student Name') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roomStudents as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student->roll_number ?? 'N/A' }}</td>
                            <td>{{ $student->user?->name ?? trim(($student->first_name ?? '').' '.($student->last_name ?? '')) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p style="text-align:center;">{{ __('No students found for this exam.') }}</p>
    @endforelse

    <div class="signature">
        <div>{{ __('Prepared by') }}: ___________________</div>
        <div>{{ __('Invigilator') }}: ___________________</div>
    </div>
</body>
</html>
