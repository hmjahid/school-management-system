<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Admission receipt') }} — {{ $admission->application_number }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; max-width: 720px; margin: 32px auto; padding: 0 24px; color: #111827; }
        .header { display: flex; align-items: center; gap: 16px; border-bottom: 2px solid #1e3a8a; padding-bottom: 16px; }
        .header img { max-height: 64px; }
        .school-name { font-size: 18px; font-weight: 700; color: #1e3a8a; }
        .school-tag { font-size: 12px; color: #6b7280; }
        h1 { font-size: 20px; margin-top: 28px; }
        dl { margin-top: 16px; display: grid; grid-template-columns: 200px 1fr; gap: 8px 16px; font-size: 14px; }
        dt { color: #6b7280; font-weight: 500; }
        dd { color: #111827; }
        .stamp { display: inline-block; margin-top: 32px; padding: 8px 16px; border: 2px solid #d97706; color: #d97706; font-weight: 700; text-transform: uppercase; transform: rotate(-3deg); letter-spacing: 0.05em; }
        .footer { margin-top: 48px; padding-top: 16px; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
        .actions { text-align: right; margin-bottom: 16px; }
        .actions button { background: #111827; color: #fff; border: 0; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        @media print { .actions, .footer-print { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">{{ __('Print') }}</button>
    </div>

    <div class="header">
        @if($site?->logo_path)
            <img src="{{ asset('storage/' . $site->logo_path) }}" alt="{{ $site->school_name }}">
        @endif
        <div>
            <div class="school-name">{{ $site->school_name ?? config('app.name') }}</div>
            @if($site?->tagline)
                <div class="school-tag">{{ $site->tagline }}</div>
            @endif
        </div>
    </div>

    <h1>{{ __('Admission payment receipt') }}</h1>

    <dl>
        <dt>{{ __('Application number') }}</dt><dd class="font-mono">{{ $admission->application_number }}</dd>
        <dt>{{ __('Applicant name') }}</dt><dd>{{ $admission->full_name }}</dd>
        <dt>{{ __('Submitted on') }}</dt><dd>{{ $admission->submitted_at?->format('M j, Y g:i A') }}</dd>
        <dt>{{ __('Class / Batch') }}</dt><dd>{{ $admission->batch?->name ?? '—' }}</dd>
        <dt>{{ __('Session') }}</dt><dd>{{ $admission->academicSession?->name ?? '—' }}</dd>
        <dt>{{ __('Admission fee') }}</dt><dd>{{ number_format((float) $admission->admission_fee, 2) }}</dd>
        <dt>{{ __('Payment number') }}</dt><dd class="font-mono">{{ $admission->payment_number ?: '—' }}</dd>
        <dt>{{ __('Payment method') }}</dt><dd>{{ $admission->payment_method ? strtoupper($admission->payment_method) : '—' }}</dd>
        <dt>{{ __('Transaction ID') }}</dt><dd class="font-mono">{{ $admission->transaction_id ?: '—' }}</dd>
        <dt>{{ __('Payment status') }}</dt><dd>{{ ucfirst($admission->payment_status) }}</dd>
    </dl>

    @if($admission->payment_status === 'submitted' || $admission->payment_status === 'unpaid')
        <div class="stamp">{{ __('Pending verification') }}</div>
    @elseif($admission->payment_status === 'verified')
        <div class="stamp" style="border-color:#059669;color:#059669">{{ __('Payment verified') }}</div>
    @elseif($admission->payment_status === 'rejected')
        <div class="stamp" style="border-color:#dc2626;color:#dc2626">{{ __('Payment rejected') }}</div>
    @endif

    <div class="footer">
        {{ __('This is a system-generated receipt.') }} {{ now()->format('M j, Y g:i A') }}
    </div>
</body>
</html>