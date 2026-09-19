@extends('layouts.app')

@section('title', __('Reset password') . ' — ' . config('app.name'))
@section('meta_description', __('Request a password reset link.'))

@section('content')
    <div class="mx-auto max-w-md px-4 py-12">
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-md">
            <h1 class="text-xl font-semibold text-gray-900">{{ __('Reset your password') }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ __('Enter your email and we will send you a link to reset your password.') }}</p>

            @if (session('status'))
                <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                    <ul class="list-inside list-disc">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="post" action="{{ route('password.email') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">{{ __('Email address') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                        class="mt-1 block w-full rounded-lg border border-gray-300 px-3 py-2 text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full rounded-md bg-blue-600 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-blue-700">
                    {{ __('Send reset link') }}
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-gray-600">
                <a href="{{ route('login') }}" class="font-medium text-blue-600 hover:text-blue-700">{{ __('Back to login') }}</a>
            </p>
        </div>
    </div>
@endsection
