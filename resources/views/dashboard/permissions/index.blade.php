@extends('layouts.dashboard')
@section('title', __('dashboard.permissions') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('dashboard.permissions') }}</h1>
</div>
<div class="space-y-6">
    @forelse($permissions as $group => $groupPermissions)
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                <h2 class="text-base font-semibold capitalize text-gray-900 dark:text-gray-100">{{ $group }}</h2>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $groupPermissions->count() }} {{ __('dashboard.permission') }}{{ $groupPermissions->count() !== 1 ? 's' : '' }}</p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                @foreach($groupPermissions as $permission)
                    <div class="flex flex-wrap items-center justify-between gap-2 px-6 py-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $permission->name }}</p>
                            @if($permission->description)
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $permission->description }}</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap gap-1">
                            @forelse($permission->roles as $role)
                                <span class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">{{ $role->name }}</span>
                            @empty
                                <span class="text-xs text-gray-400 dark:text-gray-500">—</span>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="rounded-xl border border-gray-200 bg-white p-8 text-center text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">{{ __('No permissions found.') }}</div>
    @endforelse
</div>
@endsection
