@extends('layouts.dashboard')
@section('title', __('dashboard.create_role') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('dashboard.create_role') }}</h1>
    <a href="{{ route('dashboard.roles.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.roles.store') }}" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.role') }} *</label>
            <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.guard_name') }} *</label>
            <select name="guard_name" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="web" @selected(old('guard_name', 'web') === 'web')>Web</option>
                <option value="api" @selected(old('guard_name') === 'api')>API</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Description') }}</label>
            <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">{{ old('description') }}</textarea>
        </div>
    </div>

    <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
        <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('dashboard.permissions') }}</h3>
        @foreach($permissions as $group => $groupPermissions)
            <details class="group mb-3" open>
                <summary class="flex cursor-pointer items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 [&::-webkit-details-marker]:hidden">
                    <svg class="h-4 w-4 text-gray-400 transition group-open:rotate-90 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    <span class="flex-1 capitalize">{{ $group }}</span>
                    <span class="text-xs text-gray-400 dark:text-gray-500">({{ $groupPermissions->count() }})</span>
                </summary>
                <div class="mt-2 grid gap-2 pl-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($groupPermissions as $permission)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('permissions', []))) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600">
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>

    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('dashboard.save_changes') }}</button>
</form>
@endsection
