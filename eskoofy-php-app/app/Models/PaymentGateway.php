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

    protected array $appends = ['type_label', 'is_configured'];

    public function getTypeLabelAttribute(): string
    {
        return match ((string) $this->getAttribute('type')) {
            'bank' => 'Bank',
            'mobile_financial_service' => 'Mobile Financial Service',
            'online_payment' => 'Online Payment',
            default => 'Other',
        };
    }

    /**
     * Mirrors App\Models\PaymentGateway::getIsConfiguredAttribute in the app.
     */
    public function getIsConfiguredAttribute(): bool
    {
        if (!(bool) $this->getAttribute('is_online')) {
            return true;
        }

        $has = fn (string $key): bool => trim((string) ($this->getAttribute($key) ?? '')) !== '';

        return match ((string) $this->getAttribute('code')) {
            'bkash', 'nagad', 'rocket' => $has('api_key') && $has('api_secret'),
            'uddoktapay' => $has('api_key'),
            'stripe', 'paypal', 'sslcommerz', 'paystack', 'razorpay', 'square', 'paddle'
                => $has('api_key') && $has('api_secret') && $has('callback_url'),
            default => $has('api_key') && ($has('live_url') || $has('sandbox_url')),
        };
    }
}
