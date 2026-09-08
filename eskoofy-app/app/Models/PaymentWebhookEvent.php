<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = [
        'gateway',
        'payload_hash',
        'headers',
        'payload',
        'processed_at',
        'payment_id',
        'result_status',
    ];

    protected $casts = [
        'headers' => 'array',
        'payload' => 'array',
        'processed_at' => 'datetime',
        'payment_id' => 'integer',
    ];
}
