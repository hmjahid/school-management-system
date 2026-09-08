@extends('layouts.app')

@section('title', __('Guardian Login') . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="flex w-full flex-1 items-center justify-center px-4 py-12 sm:py-16 md:py-20">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-lg sm:p-10">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-green-100">
                    <svg class="h-7 w-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">{{ __('Guardian Login') }}</h1>
                <p class="mt-2 text-sm text-gray-500">{{ __('Sign in to monitor your child\'s progress.') }}</p>
            </div>

            <form method="post" action="{{ route('guardian.login.post', $role) }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">{{ __('Password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition focus:border-green-500 focus:outline-none focus:ring-2 focus:ring-green-500/20">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-center justify-between gap-2 pt-1">
                    <div class="flex items-center gap-2">
                        <input id="remember" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}
                            class="size-4 rounded border-gray-300 text-green-600 focus:ring-green-500">
                        <label for="remember" class="text-sm text-gray-700">{{ __('Remember me') }}</label>
                    </div>
                </div>
                <button type="submit" class="mt-2 w-full rounded-lg bg-green-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500/50">
                    {{ __('Sign In') }}
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">
                    &larr; {{ __('Back to Admin Login') }}
                </a>
            </div>
        </div>
    </div>
@endsection
