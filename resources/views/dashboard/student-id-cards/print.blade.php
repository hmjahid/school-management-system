<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head><meta charset="utf-8"><title>{{ __('ID card') }} — {{ $studentIdCard->id_card_number }}</title>
<style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 20px; display: flex; justify-content: center; }
    .card { width: 320px; border: 3px solid #1e40af; border-radius: 12px; padding: 20px; text-align: center; }
    .header { border-bottom: 2px dashed #ccc; padding-bottom: 10px; margin-bottom: 10px; }
    .header h1 { margin: 0; font-size: 18px; color: #1e40af; }
    .photo { width: 80px; height: 80px; border-radius: 50%; background: #e5e7eb; margin: 10px auto; display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: bold; color: #1e40af; }
    .info { text-align: left; font-size: 13px; }
    .info p { margin: 4px 0; }
    .info strong { display: inline-block; width: 90px; color: #555; }
    .footer { margin-top: 10px; padding-top: 10px; border-top: 2px dashed #ccc; font-size: 11px; color: #888; }
    @media print { body { padding: 0; } }
</style></head>
<body>
<div class="card">
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p style="margin:2px 0;font-size:12px;color:#666;">{{ __('Student Identity Card') }}</p>
    </div>
    @php $name = $studentIdCard->student?->user?->name ?? 'Student'; $initials = implode('', array_map(fn($w) => strtoupper(substr($w,0,1)), explode(' ', $name))); @endphp
    <div class="photo">{{ $studentIdCard->photo_url ? '<img src="'.$studentIdCard->photo_url.'" style="width:80px;height:80px;border-radius:50%;object-fit:cover;">' : $initials }}</div>
    <div class="info">
        <p><strong>{{ __('Name') }}:</strong> {{ $name }}</p>
        <p><strong>{{ __('ID No') }}:</strong> {{ $studentIdCard->id_card_number }}</p>
        <p><strong>{{ __('Class') }}:</strong> {{ $studentIdCard->student?->class?->name ?? 'N/A' }}</p>
        <p><strong>{{ __('Section') }}:</strong> {{ $studentIdCard->student?->section?->name ?? 'N/A' }}</p>
        <p><strong>{{ __('Roll') }}:</strong> {{ $studentIdCard->student?->roll_number ?? 'N/A' }}</p>
        @if($studentIdCard->blood_group)<p><strong>{{ __('Blood') }}:</strong> {{ $studentIdCard->blood_group }}</p>@endif
    </div>
    <div class="footer">
        <p>{{ __('Issue date') }}: {{ $studentIdCard->issue_date?->format('d M Y') }}
        @if($studentIdCard->expiry_date) | {{ __('Expires') }}: {{ $studentIdCard->expiry_date->format('d M Y') }}@endif
        </p>
    </div>
</div>
<script>window.print();</script>
</body>
</html>