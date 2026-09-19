<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class ScheduledNotification extends Model
{
    protected static string $table = 'scheduled_notifications';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'type', 'channels', 'recipients', 'data', 'schedule',
        'scheduled_at', 'sent_at', 'status', 'error_message', 'created_by',
    ];

    protected array $casts = [
        'channels' => 'json',
        'recipients' => 'json',
        'data' => 'json',
        'schedule' => 'json',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
