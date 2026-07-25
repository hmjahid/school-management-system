<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class StudentGuardianLoginController extends Controller
{
    public function showLoginForm(string $role): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->intended(route('home'));
        }

        $view = $role === 'guardian' ? 'auth.guardian-login' : 'auth.student-login';

        return view($view, ['role' => $role]);
    }

    public function login(Request $request, string $role): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        $throttleKey = 'student_guardian_login:' . $request->ip() . '|' . strtolower($request->email);

        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = \Illuminate\Support\Facades\RateLimiter::availableIn($throttleKey);
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => $seconds]),
            ]);
        }

        $credentials = [
            'email' => $request->email,
            'password' => $request->password,
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            \Illuminate\Support\Facades\RateLimiter::hit($throttleKey, 60);

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('These credentials do not match our records.')]);
        }

        \Illuminate\Support\Facades\RateLimiter::clear($throttleKey);

        $user = Auth::user();

        if (!$user->hasRole($role)) {
            Auth::logout();

            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __('You do not have permission to access this portal.')]);
        }

        $request->session()->regenerate();

        $dashboard = $role === 'student' ? 'student.dashboard' : 'guardian.dashboard';

        return redirect()->intended(route($dashboard));
    }
}
