@extends('layouts.dashboard')

@section('title', __('dashboard.my_profile') . ' — ' . config('app.name', 'SchoolEase'))

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">{{ __('dashboard.my_profile') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.profile_description') }}</p>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">{{ session('status') }}</div>
    @endif

    <form method="post" action="{{ route('dashboard.profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('put')

        {{-- Photo + Name card --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="flex items-center gap-6">
                <div class="shrink-0">
                    <img src="{{ $user->profile_photo_url }}" alt="" class="h-20 w-20 rounded-full object-cover ring-4 ring-slate-100 dark:ring-slate-700">
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $user->name }}</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">{{ $user->email }}</p>
                    <p class="mt-1 text-xs text-slate-400 dark:text-slate-500">{{ $user->getRoleNames()->implode(', ') }}</p>
                </div>
            </div>
        </div>

        {{-- Personal Information --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-4 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('dashboard.personal_information') }}</h3>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.full_name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                    @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.phone') }}</label>
                    <input id="phone" name="phone" type="text" value="{{ old('phone', $user->phone) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                </div>

                <div>
                    <label for="gender" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.gender') }}</label>
                    <select id="gender" name="gender" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                        <option value="">{{ __('Select') }}</option>
                        <option value="male" @selected(old('gender', $user->gender) === 'male')>{{ __('Male') }}</option>
                        <option value="female" @selected(old('gender', $user->gender) === 'female')>{{ __('Female') }}</option>
                    </select>
                </div>

                <div>
                    <label for="date_of_birth" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.date_of_birth') }}</label>
                    <input id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $user->date_of_birth?->format('Y-m-d')) }}"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                </div>

                <div>
                    <label for="photo" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.profile_photo') }}</label>
                    <input id="photo" name="photo" type="file" accept="image/*"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.address') }}</label>
                    <textarea id="address" name="address" rows="2"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">{{ old('address', $user->address) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Change Password --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-1 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ __('dashboard.change_password') }}</h3>
            <p class="mb-4 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.leave_blank_password') }}</p>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.current_password') }}</label>
                    <input id="current_password" name="current_password" type="password"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                    @error('current_password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div></div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.new_password') }}</label>
                    <input id="password" name="password" type="password"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                    @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('dashboard.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                        class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-100">
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50">{{ __('dashboard.save_changes') }}</button>
        </div>
    </form>
</div>
@endsection
