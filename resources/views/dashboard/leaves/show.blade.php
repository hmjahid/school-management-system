@extends('layouts.dashboard')

@section('title', __('Leave request') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Leave request')" :description="$leave->from_date->format('M j, Y') . ' → ' . $leave->to_date->format('M j, Y')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Leaves'), 'url' => route('dashboard.leaves.index')],
                ['label' => '#' . $leave->id],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <dl class="grid gap-3 text-sm sm:grid-cols-2">
            <dt class="text-slate-500">{{ __('Teacher') }}</dt><dd class="text-slate-900">{{ $leave->teacher?->user?->name }}</dd>
            <dt class="text-slate-500">{{ __('Type') }}</dt><dd class="text-slate-900">{{ $leave->type?->name() }}</dd>
            <dt class="text-slate-500">{{ __('Status') }}</dt><dd class="text-slate-900">{{ ucfirst($leave->status) }}</dd>
            <dt class="text-slate-500">{{ __('Days') }}</dt><dd class="text-slate-900">{{ $leave->days() }}</dd>
            <dt class="text-slate-500">{{ __('Reason') }}</dt><dd class="text-slate-900 sm:col-span-2">{{ $leave->reason }}</dd>
            @if($leave->approver)
                <dt class="text-slate-500">{{ __('Decided by') }}</dt><dd class="text-slate-900">{{ $leave->approver?->name }}</dd>
                <dt class="text-slate-500">{{ __('Decided at') }}</dt><dd class="text-slate-900">{{ $leave->decided_at?->format('Y-m-d H:i') }}</dd>
            @endif
            @if($leave->approver_note)
                <dt class="text-slate-500">{{ __('Note') }}</dt><dd class="text-slate-900 sm:col-span-2">{{ $leave->approver_note }}</dd>
            @endif
        </dl>

        @can('manage_teacher_attendance')
            @if($leave->status === 'pending')
                <div class="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2">
                    <form method="post" action="{{ route('dashboard.leaves.approve', $leave) }}" class="space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-600">{{ __('Approval note (optional)') }}</label>
                        <textarea name="approver_note" rows="2" class="admin-input"></textarea>
                        <button class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">{{ __('Approve') }}</button>
                    </form>
                    <form method="post" action="{{ route('dashboard.leaves.reject', $leave) }}" class="space-y-3">
                        @csrf
                        <label class="block text-xs font-semibold text-slate-600">{{ __('Rejection reason') }}</label>
                        <textarea name="approver_note" rows="2" class="admin-input"></textarea>
                        <button class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Reject') }}</button>
                    </form>
                </div>
            @endif
        @endcan

        @if($leave->status === 'pending' && $leave->teacher?->user_id === auth()->id())
            <form method="post" action="{{ route('dashboard.leaves.cancel', $leave) }}" class="mt-6 border-t border-slate-200 pt-6">
                @csrf
                <button class="rounded-lg bg-slate-600 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700" onclick="return confirm('{{ __('Cancel this request?') }}')">{{ __('Cancel request') }}</button>
            </form>
        @endif
    </x-card>
@endsection