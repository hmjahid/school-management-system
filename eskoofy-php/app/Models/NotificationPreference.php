<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class NotificationPreference extends Model
{
    protected static string $table = 'notification_preferences';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'user_id', 'notification_type', 'email', 'sms', 'push', 'in_app',
    ];

    protected array $casts = [
        'email' => 'boolean',
        'sms' => 'boolean',
        'push' => 'boolean',
        'in_app' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
