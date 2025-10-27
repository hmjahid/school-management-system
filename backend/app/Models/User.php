<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'gender',
        'date_of_birth',
        'photo',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'date_of_birth' => 'date',
    ];
    
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the role that owns the user.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Get the refresh tokens for the user.
     */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    /**
     * Check if the user has a specific permission.
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->role && $this->role->hasPermission($permissionName)) {
            return true;
        }
        
        return $this->hasPermissionTo($permissionName);
    }

    /**
     * Create a new access token and refresh token for the user.
     *
     * @param  string  $name
     * @param  array  $abilities
     * @param  \DateTimeInterface|null  $accessTokenExpiresAt
     * @param  \DateTimeInterface|null  $refreshTokenExpiresAt
     * @return array
     */
    public function createTokenPair(string $name = 'auth_token', array $abilities = ['*'], 
        ?\DateTimeInterface $accessTokenExpiresAt = null, ?\DateTimeInterface $refreshTokenExpiresAt = null): array
    {
        $accessToken = $this->createToken($name, $abilities, $accessTokenExpiresAt);
        
        $refreshToken = $this->refreshTokens()->create([
            'token' => hash('sha256', $plainTextRefreshToken = Str::random(80)),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'expires_at' => $refreshTokenExpiresAt ?? now()->addDays(config('sanctum.refresh_token_expiration', 30)),
        ]);

        return [
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $plainTextRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTokenExpiresAt 
                ? now()->diffInSeconds($accessTokenExpiresAt) 
                : config('sanctum.expiration', 60) * 60,
        ];
    }

    /**
     * Revoke all of the user's tokens and refresh tokens.
     *
     * @return $this
     */
    public function revokeAllTokens(): static
    {
        $this->tokens()->delete();
        $this->refreshTokens()->delete();

        return $this;
    }

    /**
     * Revoke all tokens for the user except for the current one.
     *
     * @param  \Laravel\Sanctum\PersonalAccessToken  $currentToken
     * @return $this
     */
    public function revokeOtherTokens($currentToken): static
    {
        $this->tokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();
            
        $this->refreshTokens()
            ->where('id', '!=', $currentToken->id)
            ->delete();

        return $this;
    }

    /**
     * Check if the user has any of the given permissions.
     */
    public function hasAnyPermission($permissions): bool
    {
        if (is_array($permissions)) {
            foreach ($permissions as $permission) {
                if ($this->hasPermission($permission)) {
                    return true;
                }
            }
        } else {
            return $this->hasPermission($permissions);
        }
        
        return false;
    }


    
    /**
     * Get the URL to the user's profile photo.
     *
     * @return string
     */
    public function getProfilePhotoUrlAttribute(): string
    {
        return $this->photo
                    ? asset('storage/' . $this->photo)
                    : $this->defaultProfilePhotoUrl();
    }
    
    /**
     * Get the default profile photo URL if no profile photo has been uploaded.
     *
     * @return string
     */
    protected function defaultProfilePhotoUrl(): string
    {
        $name = trim(collect(explode(' ', $this->name))->map(function ($segment) {
            return mb_substr($segment, 0, 1);
        })->join(' '));

        return 'https://ui-avatars.com/api/?name='.urlencode($name).'&color=7F9CF5&background=EBF4FF';
    }
}
