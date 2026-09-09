<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class DeviceToken extends Model
{
    protected static string $table = 'device_tokens';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'user_id', 'token', 'platform', 'device_name', 'app_version',
        'last_used_at',
    ];

    protected array $casts = [
        'last_used_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
