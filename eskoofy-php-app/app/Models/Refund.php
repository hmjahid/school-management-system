<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class Refund extends Model
{
    protected static string $table = 'refunds';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'payment_id', 'user_id', 'processed_by', 'amount', 'currency',
        'transaction_id', 'status', 'reason', 'processed_at', 'metadata',
    ];

    protected array $casts = [
        'amount' => 'float',
        'processed_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
