@extends('layouts.dashboard')

@section('title', __('Staff directory') . ' — ' . config('app.name'))

@section('content')
    <x-page-header :title="__('Staff directory')" :description="__('All users with system access — teachers, admins, accountants, and more.')">
        <x-slot:breadcrumbs>
            <x-admin-breadcrumbs :items="[
                ['label' => __('Dashboard'), 'url' => route('dashboard')],
                ['label' => __('Staff')],
            ]" />
        </x-slot:breadcrumbs>
    </x-page-header>

    <form method="get" class="mb-6 flex flex-wrap items-center gap-2">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search by name or email…') }}" class="admin-input w-64">
        <select name="role" class="admin-select" onchange="this.form.submit()">
            <option value="">{{ __('All roles') }}</option>
            @foreach($roles as $r)
                <option value="{{ $r->name }}" @selected(request('role') === $r->name)>{{ $r->name }}</option>
            @endforeach
        </select>
        <x-button type="submit" variant="secondary" size="sm">{{ __('Filter') }}</x-button>
    </form>

    <x-card :padding="false">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                <tr>
                    <th class="px-4 py-3">{{ __('Name') }}</th>
                    <th class="px-4 py-3">{{ __('Email') }}</th>
                    <th class="px-4 py-3">{{ __('Roles') }}</th>
                    <th class="px-4 py-3">{{ __('Teacher profile') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($staff as $u)
                    <tr>
                        <td class="px-4 py-3 font-medium text-slate-900">{{ $u->name }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $u->email }}</td>
                        <td class="px-4 py-3">
                            @foreach($u->roles as $role)
                                <span class="inline-block rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">{{ $u->teacher?->employee_id ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">{{ __('No staff members found.') }}</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($staff->hasPages())
            <div class="border-t border-slate-200 px-4 py-3">{{ $staff->links() }}</div>
        @endif
    </x-card>
@endsection