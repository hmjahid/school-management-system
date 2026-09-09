<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

class PaymentGateway extends Model
{
    protected static string $table = 'payment_gateways';
    protected static string $primaryKey = 'id';
    protected static bool $softDeletes = true;

    protected array $fillable = [
        'name', 'code', 'type', 'is_active', 'is_online', 'has_api',
        'sandbox_url', 'live_url', 'test_mode', 'api_key', 'api_secret',
        'api_username', 'api_password', 'callback_url', 'webhook_url',
        'success_url', 'cancel_url', 'ipn_url', 'logo', 'description',
        'instructions', 'currency', 'fee_percentage', 'fee_fixed',
        'min_amount', 'max_amount', 'supported_currencies', 'extra_attributes',
        'sort_order',
    ];

    protected array $hidden = [
        'api_key', 'api_secret', 'api_username', 'api_password',
    ];

    protected array $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'has_api' => 'boolean',
        'test_mode' => 'boolean',
        'fee_percentage' => 'float',
        'fee_fixed' => 'float',
        'min_amount' => 'float',
        'max_amount' => 'float',
        'supported_currencies' => 'json',
        'extra_attributes' => 'json',
        'sort_order' => 'integer',
    ];
}
