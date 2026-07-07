@extends('layouts.app')

@section('title', site_ui('auth.login_title') . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="flex w-full flex-1 items-center justify-center px-4 py-12 sm:py-16 md:py-20">
        <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-lg sm:p-10">
            <div class="mb-6 text-center">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">{{ site_ui('auth.login_title') }}</h1>
                <p class="mt-2 text-sm text-gray-500">{{ site_ui('auth.login_intro') }}</p>
            </div>

            <form method="post" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">{{ site_ui('auth.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">{{ site_ui('auth.password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 px-3 py-2.5 text-gray-900 shadow-sm transition focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                </div>
                <div class="flex items-center justify-between gap-2 pt-1">
                    <div class="flex items-center gap-2">
                        <input id="remember" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}
                            class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="remember" class="text-sm text-gray-700">{{ site_ui('auth.remember') }}</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">{{ __('Forgot password?') }}</a>
                </div>
                <button type="submit" class="mt-2 w-full rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500/50">
                    {{ site_ui('auth.sign_in') }}
                </button>
            </form>
        </div>
    </div>
@endsection
