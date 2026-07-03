@extends('layouts.app')

@section('title', __('Set new password') . ' — ' . config('app.name'))
@section('meta_description', __('Set a new password for your account.'))

@section('content')
    <div class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-md">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Set a new password') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Choose a strong password you do not use anywhere else.') }}</p>

            @if ($errors->any())
                <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $li ?? '' }}{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus autocomplete="username"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">{{ __('New password') }}</label>
                    <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">{{ __('At least 8 characters.') }}</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">{{ __('Confirm new password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>

                <button type="submit" class="w-full rounded-md bg-blue-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                    {{ __('Reset password') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700">{{ __('Back to login') }}</a>
            </p>
        </div>
    </div>
@endsection
