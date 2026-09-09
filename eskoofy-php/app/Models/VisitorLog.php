<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class VisitorLog extends Model
{
    protected static string $table = 'visitor_logs';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'ip', 'url', 'method', 'user_agent', 'referer', 'user_id',
    ];

    protected array $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
