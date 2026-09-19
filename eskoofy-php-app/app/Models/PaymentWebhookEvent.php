<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class PaymentWebhookEvent extends Model
{
    protected static string $table = 'payment_webhook_events';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = false;

    protected array $fillable = [
        'gateway', 'payload_hash', 'headers', 'payload', 'processed_at',
        'payment_id', 'result_status',
    ];

    protected array $casts = [
        'headers' => 'json',
        'payload' => 'json',
        'processed_at' => 'datetime',
        'payment_id' => 'integer',
    ];
}
