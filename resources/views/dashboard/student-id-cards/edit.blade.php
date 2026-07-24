@extends('layouts.dashboard')
@section('title', __('Edit ID card') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900">{{ __('Edit ID card') }}</h1>
    <a href="{{ route('dashboard.student-id-cards.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.student-id-cards.update', $studentIdCard) }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    @csrf @method('put')
    <div class="grid gap-4 sm:grid-cols-2">
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Student') }} *</label><select name="student_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">@foreach($students as $s)<option value="{{ $s->id }}" @selected($studentIdCard->student_id == $s->id)>{{ $s->user?->name }} ({{ $s->admission_number }})</option>@endforeach</select></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Issue date') }} *</label><input type="date" name="issue_date" value="{{ old('issue_date', $studentIdCard->issue_date?->format('Y-m-d')) }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Expiry date') }}</label><input type="date" name="expiry_date" value="{{ old('expiry_date', $studentIdCard->expiry_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Blood group') }}</label><input name="blood_group" value="{{ old('blood_group', $studentIdCard->blood_group) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Photo URL') }}</label><input name="photo_url" value="{{ old('photo_url', $studentIdCard->photo_url) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"></div>
        <div><label class="block text-sm font-medium text-gray-700">{{ __('Status') }} *</label><select name="status" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"><option value="active" @selected(old('status', $studentIdCard->status) === 'active')>{{ __('Active') }}</option><option value="expired" @selected(old('status', $studentIdCard->status) === 'expired')>{{ __('Expired') }}</option><option value="revoked" @selected(old('status', $studentIdCard->status) === 'revoked')>{{ __('Revoked') }}</option></select></div>
    </div>
    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('Update') }}</button>
</form>
@endsection