@extends('layouts.dashboard')
@section('title', __('dashboard.create_user') . ' — ' . config('app.name'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ __('dashboard.create_user') }}</h1>
    <a href="{{ route('dashboard.users.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">{{ __('Back') }}</a>
</div>
@include('dashboard.partials.form-errors')
<form method="post" action="{{ route('dashboard.users.store') }}" enctype="multipart/form-data" class="space-y-6 rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    @csrf
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.full_name') }} *</label>
            <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.email') }} *</label>
            <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.phone') }}</label>
            <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.password') }} *</label>
            <input type="password" name="password" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.confirm_password') }} *</label>
            <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.role') }} *</label>
            <select name="role_id" required class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                <option value="">{{ __('Select role') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('dashboard.profile_photo') }}</label>
            <input type="file" name="photo" accept="image/*" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
        </div>
    </div>

    @if($permissions->count())
        <div class="border-t border-gray-200 pt-6 dark:border-gray-700">
            <h3 class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('dashboard.direct_permissions') }}</h3>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($permissions as $permission)
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" name="direct_permissions[]" value="{{ $permission->name }}" @checked(in_array($permission->name, old('direct_permissions', []))) class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600">
                        {{ $permission->name }}
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <button type="submit" class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ __('dashboard.save_changes') }}</button>
</form>
@endsection
