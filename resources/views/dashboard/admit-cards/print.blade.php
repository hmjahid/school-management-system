<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><title>{{ __('Admit card') }} — {{ $admitCard->admit_card_number }}</title>
<style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
    .card { max-width: 600px; margin: auto; border: 3px solid #1e40af; border-radius: 12px; padding: 30px; }
    .header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 15px; margin-bottom: 15px; }
    .header h1 { margin: 0; font-size: 24px; color: #1e40af; }
    .header h2 { margin: 5px 0 0; font-size: 18px; color: #333; }
    .info { display: grid; grid-template-columns: 1fr 2fr; gap: 8px; font-size: 14px; }
    .info dt { font-weight: bold; color: #555; }
    .info dd { margin: 0; color: #222; }
    .footer { text-align: center; margin-top: 20px; padding-top: 15px; border-top: 2px dashed #ccc; font-size: 12px; color: #888; }
    @media print { body { padding: 0; } .card { border: 2px solid #000; } }
</style></head>
<body>
<div class="card">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <h2>{{ __('Admit Card') }}</h2>
    </div>
    <dl class="info">
        <dt>{{ __('Student Name') }}:</dt><dd>{{ $admitCard->student?->user?->name }}</dd>
        <dt>{{ __('Class') }}:</dt><dd>{{ $admitCard->student?->class?->name ?? 'N/A' }}</dd>
        <dt>{{ __('Section') }}:</dt><dd>{{ $admitCard->student?->section?->name ?? 'N/A' }}</dd>
        <dt>{{ __('Roll Number') }}:</dt><dd>{{ $admitCard->student?->roll_number ?? 'N/A' }}</dd>
        <dt>{{ __('Exam') }}:</dt><dd>{{ $admitCard->exam?->name }}</dd>
        <dt>{{ __('Card Number') }}:</dt><dd>{{ $admitCard->admit_card_number }}</dd>
        <dt>{{ __('Issue Date') }}:</dt><dd>{{ $admitCard->issue_date?->format('d M Y') }}</dd>
    </dl>
    <div class="footer">
        <p>{{ __('This admit card is valid for the exam mentioned above.') }}</p>
        <p>{{ __('Authorized signature') }}: ___________________</p>
    </div>
</div>
<script>window.print();</script>
</body>
</html>