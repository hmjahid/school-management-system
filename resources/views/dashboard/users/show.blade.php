@extends('layouts.dashboard')
@section('title', $user->name . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('dashboard.user_details') }}</h1>
    <div class="flex gap-2">
        <a href="{{ route('dashboard.users.index') }}" class="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">{{ __('Back') }}</a>
        @can('manage_users')
            <a href="{{ route('dashboard.users.edit', $user) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ __('Edit') }}</a>
            <form method="post" action="{{ route('dashboard.users.destroy', $user) }}" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                @csrf @method('delete')
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">{{ __('Delete') }}</button>
            </form>
        @endcan
    </div>
</div>
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-1">
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="mx-auto h-24 w-24 rounded-full object-cover ring-4 ring-gray-100 dark:ring-gray-700">
            <h2 class="mt-4 text-lg font-bold text-gray-900 dark:text-gray-100">{{ $user->name }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
            @foreach($user->roles as $role)
                <span class="mt-2 inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">{{ $role->name }}</span>
            @endforeach
        </div>
    </div>
    <div class="lg:col-span-2 space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <dl class="space-y-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.full_name') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.email') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.phone') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $user->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.role') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $user->schoolRole?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.assigned_roles') }}</dt>
                    <dd>
                        @forelse($user->roles as $role)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">{{ $role->name }}</span>
                        @empty
                            <span class="text-gray-400">—</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('dashboard.direct_permissions') }}</dt>
                    <dd>
                        @forelse($user->permissions as $permission)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">{{ $permission->name }}</span>
                        @empty
                            <span class="text-gray-400">—</span>
                        @endforelse
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('Created') }}</dt>
                    <dd class="font-medium text-gray-900 dark:text-gray-100">{{ $user->created_at->format('d M Y, h:i A') }}</dd>
                </div>
            </dl>
        </div>
    </div>
</div>
@endsection
