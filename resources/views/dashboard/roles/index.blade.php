@extends('layouts.dashboard')
@section('title', __('dashboard.roles') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('dashboard.roles') }}</h1>
    @can('manage_roles')
        <a href="{{ route('dashboard.roles.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('dashboard.create_role') }}</a>
    @endcan
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('Search roles...') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Search') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('dashboard.role') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Description') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('dashboard.guard_name') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Users') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($roles as $role)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $role->name }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $role->description ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $role->guard_name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-700 dark:text-gray-300">{{ $role->users_count }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @can('manage_roles')
                            <a href="{{ route('dashboard.roles.edit', $role) }}" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('Edit') }}</a>
                            @if($role->name !== 'admin')
                                <form method="post" action="{{ route('dashboard.roles.destroy', $role) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                    @csrf @method('delete')
                                    <button type="submit" class="ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">{{ __('Delete') }}</button>
                                </form>
                            @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('dashboard.no_roles_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $roles->links() }}</div>
@endsection
