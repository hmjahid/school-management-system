<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ __('Admission approval letter') }} — {{ $admission->application_number }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; max-width: 720px; margin: 48px auto; padding: 0 24px; color: #111827; line-height: 1.6; }
        .actions { text-align: right; margin-bottom: 16px; }
        .actions button { background: #111827; color: #fff; border: 0; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 13px; }
        .letterhead { text-align: center; border-bottom: 2px solid #1e3a8a; padding-bottom: 16px; }
        .letterhead img { max-height: 80px; }
        .letterhead .name { font-size: 24px; font-weight: 700; color: #1e3a8a; margin-top: 8px; }
        .letterhead .tag { font-size: 12px; color: #6b7280; font-style: italic; }
        h1 { text-align: center; font-size: 18px; margin: 32px 0 8px; letter-spacing: 0.05em; text-transform: uppercase; }
        .meta { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 24px; color: #374151; }
        .body { font-size: 14px; text-align: justify; }
        .sign { margin-top: 48px; display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
        .sign div { border-top: 1px solid #111827; padding-top: 8px; font-size: 12px; color: #6b7280; text-align: center; }
        .stamp { position: absolute; right: 24px; top: 240px; padding: 16px 24px; border: 4px double #059669; color: #059669; font-weight: 700; text-transform: uppercase; transform: rotate(-12deg); font-size: 18px; letter-spacing: 0.1em; }
        @media print { .actions { display: none; } body { margin: 0; } .stamp { position: fixed; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()">{{ __('Print') }}</button>
    </div>

    <div class="letterhead">
        @if($site?->logo_path)
            <img src="{{ asset('storage/' . $site->logo_path) }}" alt="{{ $site->school_name }}">
        @endif
        <div class="name">{{ $site->school_name ?? config('app.name') }}</div>
        @if($site?->tagline)
            <div class="tag">{{ $site->tagline }}</div>
        @endif
        @if($site?->address)
            <div class="tag">{{ $site->address }}</div>
        @endif
    </div>

    <div class="stamp">{{ __('Approved') }}</div>

    <h1>{{ __('Admission approval letter') }}</h1>

    <div class="meta">
        <div>{{ __('Ref') }}: {{ $admission->application_number }}</div>
        <div>{{ __('Date') }}: {{ $admission->verified_at?->format('M j, Y') ?? now()->format('M j, Y') }}</div>
    </div>

    <div class="body">
        <p>{{ __('To') }},<br><strong>{{ $admission->full_name }}</strong><br>{{ $admission->address ?? '' }}</p>

        <p>{!! __('Dear :name,', ['name' => $admission->first_name]) !!}</p>

        <p>
            {!! __('Congratulations! We are pleased to inform you that your admission application (reference number <strong>:num</strong>) has been <strong>approved</strong>. Your payment has been verified successfully on :date.', [
                'num' => $admission->application_number,
                'date' => $admission->verified_at?->format('M j, Y') ?? '—',
            ]) !!}
        </p>

        <p>
            {{ __('You have been admitted to') }} <strong>{{ $admission->batch?->name ?? '—' }}</strong>
            @if($admission->academicSession?->name)
                {{ __('for the academic session') }} <strong>{{ $admission->academicSession->name }}</strong>.
            @endif
        </p>

        <p>
            {{ __('Please bring this letter and a valid photo ID to the school office within fourteen (14) days to complete your enrollment formalities.') }}
        </p>

        <p>{{ __('We look forward to welcoming you to our school community.') }}</p>
    </div>

    <div class="sign">
        <div>{{ __('Authorized signature') }}</div>
        <div>{{ __('Principal / Head of School') }}</div>
    </div>
</body>
</html>