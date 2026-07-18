<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RefreshToken extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'token',
        'ip_address',
        'user_agent',
        'expires_at',
        'last_used_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Generate a random token when creating a new refresh token
        static::creating(function ($refreshToken) {
            $refreshToken->token = hash('sha256', Str::random(80));
        });
    }

    /**
     * Get the user that owns the refresh token.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the token has expired.
     *
     * @return bool
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark the token as used.
     *
     * @return $this
     */
    public function markAsUsed()
    {
        $this->last_used_at = now();
        $this->save();

        return $this;
    }
}
