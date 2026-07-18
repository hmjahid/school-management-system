<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RefreshTokenRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use App\Support\ApiResponse;
use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $roleName = $request->roleName();

            $role = Role::where('name', $roleName)->first();
            if (! $role) {
                return $this->error('Specified role not found', 400, null, 'INVALID_ROLE');
            }

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id,
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            $user->assignRole($roleName);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->created([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('roles'),
            ], 'Registration successful');
        } catch (Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return $this->error(
                'Registration failed. Please try again.',
                500,
                config('app.debug') ? ['exception' => $e->getMessage()] : null,
                'REGISTRATION_FAILED'
            );
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');

            if (! Auth::attempt($credentials, $request->boolean('remember_me'))) {
                return $this->error('Invalid login details', 401, null, 'INVALID_CREDENTIALS');
            }

            $user = User::where('email', $request->email)->firstOrFail();

            $tokenExpiration = $request->boolean('remember_me')
                ? now()->addDays(config('sanctum.remember_token_expiration', 30))
                : now()->addMinutes(config('sanctum.expiration', 60));

            $tokenData = $user->createTokenPair(
                'auth_token',
                ['*'],
                $tokenExpiration
            );

            $user->update(['last_login_at' => now()]);

            return $this->success([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_type' => $tokenData['token_type'],
                'expires_in' => $tokenData['expires_in'],
                'user' => $user->load('roles'),
            ], 'Login successful');
        } catch (Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'email' => $request->email,
            ]);

            return $this->error(
                'Login failed. Please try again.',
                500,
                config('app.debug') ? ['exception' => $e->getMessage()] : null,
                'LOGIN_FAILED'
            );
        }
    }

    public function me(Request $request)
    {
        return $this->success($request->user()->load('roles', 'permissions'));
    }

    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            $user->revokeAllTokens();

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->success(['session_cleared' => true], 'Successfully logged out');
        } catch (Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return $this->error(
                'Error during logout',
                500,
                config('app.debug') ? ['exception' => $e->getMessage()] : null,
                'LOGOUT_FAILED'
            );
        }
    }

    public function refreshToken(RefreshTokenRequest $request)
    {
        try {
            $user = $request->user();

            $refreshToken = $user->refreshTokens()
                ->where('token', hash('sha256', $request->validated('refresh_token')))
                ->where('expires_at', '>', now())
                ->first();

            if (! $refreshToken) {
                throw new AuthenticationException('Invalid or expired refresh token');
            }

            $refreshToken->markAsUsed();

            $tokenData = $user->createTokenPair(
                'auth_token',
                ['*'],
                now()->addMinutes(config('sanctum.expiration', 60)),
                now()->addDays(config('sanctum.refresh_token_expiration', 30))
            );

            Log::info('Token refreshed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
            ]);

            return $this->success([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_type' => $tokenData['token_type'],
                'expires_in' => $tokenData['expires_in'],
                'user' => $user->load('roles'),
            ], 'Token refreshed');
        } catch (Exception $e) {
            $userId = $request->user()?->id;

            Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'user_id' => $userId,
            ]);

            if ($e instanceof AuthenticationException && $userId) {
                User::find($userId)?->revokeAllTokens();
            }

            return $this->error(
                'Failed to refresh token. Please log in again.',
                401,
                ['requires_login' => true],
                'TOKEN_REFRESH_FAILED'
            );
        }
    }
}
