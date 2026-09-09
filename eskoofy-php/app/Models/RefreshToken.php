<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class RefreshToken extends Model
{
    protected static string $table = 'refresh_tokens';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'token', 'ip_address', 'user_agent', 'expires_at', 'last_used_at',
    ];

    protected array $hidden = ['token'];

    protected array $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
