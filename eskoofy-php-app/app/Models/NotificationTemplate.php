<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class NotificationTemplate extends Model
{
    protected static string $table = 'notification_templates';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'name', 'key', 'subject', 'content', 'sms_content',
        'in_app_content', 'variables', 'is_active',
    ];

    protected array $casts = [
        'variables' => 'json',
        'is_active' => 'boolean',
    ];
}
