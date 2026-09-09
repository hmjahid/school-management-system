<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class NotificationLog extends Model
{
    protected static string $table = 'notification_logs';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'type', 'notifiable_type', 'notifiable_id', 'content', 'channel',
        'status', 'error_message', 'sent_at', 'delivered_at', 'opened_at',
        'metadata',
    ];

    protected array $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function notifiable()
    {
        return $this->morphTo();
    }
}
