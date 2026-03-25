<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Exception;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            Log::info('Registration request received', $request->all());
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => ['required', 'confirmed', Rules\Password::defaults()],
                'role' => 'required|in:admin,teacher,student,parent,accountant',
            ]);

            // Check if role exists
            $role = Role::where('name', $validated['role'])->first();
            if (!$role) {
                Log::error('Role not found', ['role' => $validated['role']]);
                return response()->json(['message' => 'Specified role not found'], 400);
            }

            // Create user with role_id
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role_id' => $role->id, // Set the role_id from the role we found
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]);

            Log::info('User created successfully', ['user_id' => $user->id]);

            try {
                // Assign role to user
                $user->assignRole($validated['role']);
                Log::info('Role assigned successfully', ['user_id' => $user->id, 'role' => $validated['role']]);
            } catch (Exception $e) {
                Log::error('Failed to assign role', [
                    'user_id' => $user->id,
                    'role' => $validated['role'],
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user->load('roles')
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Login user and create tokens
     */
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'remember_me' => 'boolean'
            ]);

            $credentials = $request->only('email', 'password');

            if (!Auth::attempt($credentials, $request->boolean('remember_me'))) {
                return response()->json([
                    'message' => 'Invalid login details'
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            
            // Create token pair with expiration
            $tokenExpiration = $request->remember_me 
                ? now()->addDays(config('sanctum.remember_token_expiration', 30))
                : now()->addMinutes(config('sanctum.expiration', 60));
                
            $tokenData = $user->createTokenPair(
                'auth_token',
                ['*'],
                $tokenExpiration
            );

            // Update last login timestamp
            $user->update(['last_login_at' => now()]);

            return response()->json([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_type' => $tokenData['token_type'],
                'expires_in' => $tokenData['expires_in'],
                'user' => $user->load('roles')
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'email' => $request->email
            ]);
            
            return response()->json([
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get the authenticated user
     */
    public function me(Request $request)
    {
        return response()->json($request->user()->load('roles', 'permissions'));
    }

    /**
     * Logout user (Revoke all tokens)
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();
            
            // Invalidate all tokens for this user
            $user->revokeAllTokens();
            
            // Clear the session
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return response()->json([
                'message' => 'Successfully logged out',
                'session_cleared' => true
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id
            ]);
            
            return response()->json([
                'message' => 'Error during logout',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Refresh the access token using refresh token with rotation
     */
    public function refreshToken(Request $request)
    {
        try {
            $request->validate([
                'refresh_token' => 'required|string',
            ]);

            // Get the current user
            $user = $request->user();
            
            // Find the refresh token
            $refreshToken = $user->refreshTokens()
                ->where('token', hash('sha256', $request->refresh_token))
                ->where('expires_at', '>', now())
                ->first();

            if (!$refreshToken) {
                throw new \Illuminate\Auth\AuthenticationException('Invalid or expired refresh token');
            }
            
            // Mark the old refresh token as used
            $refreshToken->markAsUsed();
            
            // Create new token pair
            $tokenData = $user->createTokenPair(
                'auth_token',
                ['*'],
                now()->addMinutes(config('sanctum.expiration', 60)),
                now()->addDays(config('sanctum.refresh_token_expiration', 30))
            );
            
            // Log the refresh event
            \Log::info('Token refreshed', [
                'user_id' => $user->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'],
                'token_type' => $tokenData['token_type'],
                'expires_in' => $tokenData['expires_in'],
                'user' => $user->load('roles')
            ]);
            
        } catch (\Exception $e) {
            $userId = $request->user()?->id;
            
            // Log the error with context
            \Log::error('Token refresh failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'user_id' => $userId
            ]);
            
            // Invalidate all user's sessions on security exception
            if ($e instanceof \Illuminate\Auth\AuthenticationException && $userId) {
                $user = User::find($userId);
                if ($user) {
                    $user->revokeAllTokens();
                }
            }
            
            return response()->json([
                'message' => 'Failed to refresh token. Please log in again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'requires_login' => true
            ], 401);
        }
    }
    
    /**
     * Create a new refresh token for the user
     */
    protected function createRefreshToken($user)
    {
        $token = \Illuminate\Support\Str::random(80);
        
        $user->refreshTokens()->create([
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDays(config('sanctum.refresh_token_expiration', 30)),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        
        return $token;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
