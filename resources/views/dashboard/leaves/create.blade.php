@extends('layouts.dashboard')

@section('title', __('New leave request') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('New leave request')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Leaves'), 'url' => route('dashboard.leaves.index')],
                ['label' => __('New')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <x-card>
        @include('dashboard.partials.form-errors')
        <form method="post" action="{{ route('dashboard.leaves.store') }}" class="space-y-5">
            @csrf
            @if(auth()->user()->hasAnyRole(['admin','staff']))
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Teacher') }}</label>
                    <select name="teacher_id" required class="admin-select">
                        <option value="">—</option>
                        @foreach($teachers as $t)
                            <option value="{{ $t->id }}" @selected(old('teacher_id', $teacher?->id) == $t->id)>{{ $t->user?->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="teacher_id" value="{{ $teacher->id }}">
                <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700">{{ __('Teacher') }}: <strong>{{ $teacher->user?->name }}</strong></p>
            @endif

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Leave type') }}</label>
                <select name="leave_type_id" required class="admin-select">
                    <option value="">—</option>
                    @foreach($types as $t)
                        <option value="{{ $t->id }}" @selected(old('leave_type_id') == $t->id)>{{ $t->name() }} ({{ $t->days_per_year }} days/yr)</option>
                    @endforeach
                </select>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('From date') }}</label>
                    <input type="date" name="from_date" required value="{{ old('from_date') }}" class="admin-input">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('To date') }}</label>
                    <input type="date" name="to_date" required value="{{ old('to_date') }}" class="admin-input">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">{{ __('Reason') }}</label>
                <textarea name="reason" rows="4" required class="admin-input">{{ old('reason') }}</textarea>
            </div>
            <div class="flex justify-end gap-2">
                <x-button :href="route('dashboard.leaves.index')" variant="ghost">{{ __('Cancel') }}</x-button>
                <x-button type="submit">{{ __('Submit') }}</x-button>
            </div>
        </form>
    </x-card>
@endsection