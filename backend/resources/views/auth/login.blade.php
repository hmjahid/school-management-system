@extends('layouts.app')

@section('title', site_ui('auth.login_title') . ' — ' . config('app.name', 'School'))

@section('content')
    <div class="mx-auto max-w-md rounded-2xl border border-gray-200 bg-white p-8 shadow-md">
        <h1 class="text-xl font-semibold text-gray-900">{{ site_ui('auth.login_title') }}</h1>
        <p class="mt-1 text-sm text-gray-500">{{ site_ui('auth.login_intro') }}</p>

        <form method="post" action="{{ route('login') }}" class="mt-8 space-y-5">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">{{ site_ui('auth.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">{{ site_ui('auth.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password"
                    class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
            <div class="flex items-center gap-2">
                <input id="remember" name="remember" type="checkbox" value="1" {{ old('remember') ? 'checked' : '' }}
                    class="size-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="remember" class="text-sm text-gray-700">{{ site_ui('auth.remember') }}</label>
            </div>
            <button type="submit" class="w-full rounded-md bg-blue-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                {{ site_ui('auth.sign_in') }}
            </button>
        </form>
    </div>
@endsection
