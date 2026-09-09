<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Message extends Model
{
    protected static string $table = 'messages';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'sender_id', 'receiver_id', 'subject', 'body', 'read_at',
    ];

    protected array $casts = [
        'read_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }
}
