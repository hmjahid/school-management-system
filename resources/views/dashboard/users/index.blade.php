@extends('layouts.dashboard')
@section('title', __('dashboard.users') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('dashboard.users') }}</h1>
    @can('manage_users')
        <a href="{{ route('dashboard.users.create') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ __('dashboard.add_user') }}</a>
    @endcan
</div>
<form method="get" class="mb-6 flex flex-wrap gap-3">
    <input name="search" value="{{ request('search') }}" placeholder="{{ __('dashboard.search_users') }}" class="rounded-lg border border-gray-300 px-3 py-2 text-sm flex-1 min-w-[200px] dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
    <select name="role_id" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
        <option value="">{{ __('All roles') }}</option>
        @foreach($roles as $role)
            <option value="{{ $role->id }}" @selected(request('role_id') == $role->id)>{{ $role->name }}</option>
        @endforeach
    </select>
    <button type="submit" class="rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700">{{ __('Filter') }}</button>
</form>
<div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-700">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Photo') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('dashboard.full_name') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('dashboard.email') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('dashboard.phone') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('dashboard.role') }}</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 dark:text-gray-300">{{ __('Actions') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($users as $u)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-4 py-3">
                        <img src="{{ $u->profile_photo_url }}" alt="{{ $u->name }}" class="h-8 w-8 rounded-full object-cover">
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{{ $u->name }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $u->email }}</td>
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $u->phone ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @foreach($u->roles as $role)
                            <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">{{ $role->name }}</span>
                        @endforeach
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('dashboard.users.show', $u) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">{{ __('View') }}</a>
                        @can('manage_users')
                            <a href="{{ route('dashboard.users.edit', $u) }}" class="ml-2 text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">{{ __('Edit') }}</a>
                            <form method="post" action="{{ route('dashboard.users.destroy', $u) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure?') }}')">
                                @csrf @method('delete')
                                <button type="submit" class="ml-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">{{ __('Delete') }}</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">{{ __('dashboard.no_users_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
