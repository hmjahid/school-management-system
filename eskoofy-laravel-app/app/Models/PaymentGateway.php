<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentGateway extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Gateway types
    public const TYPE_BANK = 'bank';

    public const TYPE_MOBILE_FINANCIAL_SERVICE = 'mobile_financial_service';

    public const TYPE_ONLINE_PAYMENT = 'online_payment';

    public const TYPE_OTHER = 'other';

    // Common gateways
    public const GATEWAY_BKASH = 'bkash';

    public const GATEWAY_NAGAD = 'nagad';

    public const GATEWAY_ROCKET = 'rocket';

    public const GATEWAY_UDDOKTAPAY = 'uddoktapay';

    public const GATEWAY_STRIPE = 'stripe';

    public const GATEWAY_PAYPAL = 'paypal';

    public const GATEWAY_PADDLE = 'paddle';

    // International gateways (shipped disabled by default, enabled per install)
    public const GATEWAY_GOOGLE_PAY = 'gpay';

    public const GATEWAY_APPLE_PAY = 'applepay';

    public const GATEWAY_RAZORPAY = 'razorpay';

    public const GATEWAY_PAYSTACK = 'paystack';

    public const GATEWAY_FLUTTERWAVE = 'flutterwave';

    public const GATEWAY_SSLCOMMERZ = 'sslcommerz';

    public const GATEWAY_SQUARE = 'square';

    public const GATEWAY_MOLLIE = 'mollie';

    public const GATEWAY_AUTHORIZE_NET = 'authorize_net';

    public const GATEWAY_XENDIT = 'xendit';

    public const GATEWAY_ADYEN = 'adyen';

    public const GATEWAY_SKRILL = 'skrill';

    public const GATEWAY_CASH = 'cash';

    public const GATEWAY_CHEQUE = 'cheque';

    public const GATEWAY_BANK_TRANSFER = 'bank_transfer';

    /**
     * Gateways handled by the config-driven GenericHostedGatewayAdapter:
     * hosted checkout endpoint + api key (+ optional api secret).
     *
     * @var array<int, string>
     */
    public const CONFIG_DRIVEN_GATEWAYS = [
        self::GATEWAY_GOOGLE_PAY,
        self::GATEWAY_APPLE_PAY,
        self::GATEWAY_RAZORPAY,
        self::GATEWAY_PAYSTACK,
        self::GATEWAY_FLUTTERWAVE,
        self::GATEWAY_SSLCOMMERZ,
        self::GATEWAY_SQUARE,
        self::GATEWAY_MOLLIE,
        self::GATEWAY_AUTHORIZE_NET,
        self::GATEWAY_XENDIT,
        self::GATEWAY_ADYEN,
        self::GATEWAY_SKRILL,
    ];

    /**
     * Display names for the built-in international gateways.
     *
     * @var array<string, string>
     */
    public const INTERNATIONAL_GATEWAY_NAMES = [
        self::GATEWAY_GOOGLE_PAY => 'Google Pay',
        self::GATEWAY_APPLE_PAY => 'Apple Pay',
        self::GATEWAY_RAZORPAY => 'Razorpay',
        self::GATEWAY_PAYSTACK => 'Paystack',
        self::GATEWAY_FLUTTERWAVE => 'Flutterwave',
        self::GATEWAY_SSLCOMMERZ => 'SSLCommerz',
        self::GATEWAY_SQUARE => 'Square',
        self::GATEWAY_MOLLIE => 'Mollie',
        self::GATEWAY_AUTHORIZE_NET => 'Authorize.Net',
        self::GATEWAY_XENDIT => 'Xendit',
        self::GATEWAY_ADYEN => 'Adyen',
        self::GATEWAY_SKRILL => 'Skrill',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
        'is_online',
        'has_api',
        'sandbox_url',
        'live_url',
        'test_mode',
        'api_key',
        'api_secret',
        'api_username',
        'api_password',
        'callback_url',
        'webhook_url',
        'success_url',
        'cancel_url',
        'ipn_url',
        'logo',
        'description',
        'instructions',
        'currency',
        'fee_percentage',
        'fee_fixed',
        'min_amount',
        'max_amount',
        'supported_currencies',
        'extra_attributes',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online' => 'boolean',
        'has_api' => 'boolean',
        'test_mode' => 'boolean',
        'fee_percentage' => 'decimal:2',
        'fee_fixed' => 'decimal:2',
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'supported_currencies' => 'array',
        'extra_attributes' => 'array',
        'sort_order' => 'integer',
        'api_key' => 'encrypted',
        'api_secret' => 'encrypted',
        'api_username' => 'encrypted',
        'api_password' => 'encrypted',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'api_username',
        'api_password',
    ];

    protected $appends = [
        'type_label',
        'is_configured',
        'logo_url',
    ];

    /**
     * The "booting" method of the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($gateway) {
            // Ensure code is lowercase and slugified
            $gateway->code = strtolower(preg_replace('/[^A-Za-z0-9_]/', '_', $gateway->code));

            // Set default values
            if (empty($gateway->currency)) {
                $gateway->currency = 'BDT';
            }

            if (empty($gateway->supported_currencies)) {
                $gateway->supported_currencies = [$gateway->currency];
            }
        });
    }

    /**
     * Get the type label.
     */
    public function getTypeLabelAttribute(): string
    {
        $types = [
            self::TYPE_BANK => 'Bank',
            self::TYPE_MOBILE_FINANCIAL_SERVICE => 'Mobile Financial Service',
            self::TYPE_ONLINE_PAYMENT => 'Online Payment',
            self::TYPE_OTHER => 'Other',
        ];

        return $types[$this->type] ?? 'Unknown';
    }

    /**
     * Check if the gateway is properly configured.
     */
    public function getIsConfiguredAttribute(): bool
    {
        if (! $this->is_online) {
            return true; // Offline gateways don't need API configuration
        }

        // Check required API credentials based on gateway type
        switch ($this->code) {
            case self::GATEWAY_BKASH:
            case self::GATEWAY_NAGAD:
            case self::GATEWAY_ROCKET:
                return ! empty($this->api_key) && ! empty($this->api_secret);

            case self::GATEWAY_UDDOKTAPAY:
                return ! empty($this->api_key);

            case self::GATEWAY_STRIPE:
            case self::GATEWAY_PAYPAL:
            case self::GATEWAY_SSLCOMMERZ:
            case self::GATEWAY_PAYSTACK:
            case self::GATEWAY_RAZORPAY:
            case self::GATEWAY_SQUARE:
            case self::GATEWAY_PADDLE:
                return ! empty($this->api_key) && ! empty($this->api_secret) && ! empty($this->callback_url);

            default:
                // Config-driven gateways (the built-in international presets and
                // any gateway added manually) need a key and a checkout endpoint.
                return ! empty($this->api_key)
                    && (! empty($this->live_url) || ! empty($this->sandbox_url));
        }
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo)) {
            // Return default logo based on gateway code
            $defaultLogos = [
                self::GATEWAY_BKASH => 'https://example.com/images/gateways/bkash.png',
                self::GATEWAY_NAGAD => 'https://example.com/images/gateways/nagad.png',
                self::GATEWAY_ROCKET => 'https://example.com/images/gateways/rocket.png',
                self::GATEWAY_UDDOKTAPAY => 'https://example.com/images/gateways/uddoktapay.png',
                self::GATEWAY_STRIPE => 'https://example.com/images/gateways/stripe.png',
                self::GATEWAY_PAYPAL => 'https://example.com/images/gateways/paypal.png',
                self::GATEWAY_SSLCOMMERZ => 'https://example.com/images/gateways/sslcommerz.png',
                self::GATEWAY_PAYSTACK => 'https://example.com/images/gateways/paystack.png',
                self::GATEWAY_RAZORPAY => 'https://example.com/images/gateways/razorpay.png',
                self::GATEWAY_SQUARE => 'https://example.com/images/gateways/square.png',
                self::GATEWAY_GOOGLE_PAY => 'https://example.com/images/gateways/google-pay.png',
                self::GATEWAY_APPLE_PAY => 'https://example.com/images/gateways/apple-pay.png',
                self::GATEWAY_FLUTTERWAVE => 'https://example.com/images/gateways/flutterwave.png',
                self::GATEWAY_MOLLIE => 'https://example.com/images/gateways/mollie.png',
                self::GATEWAY_AUTHORIZE_NET => 'https://example.com/images/gateways/authorize-net.png',
                self::GATEWAY_XENDIT => 'https://example.com/images/gateways/xendit.png',
                self::GATEWAY_ADYEN => 'https://example.com/images/gateways/adyen.png',
                self::GATEWAY_SKRILL => 'https://example.com/images/gateways/skrill.png',
                self::GATEWAY_PADDLE => 'https://example.com/images/gateways/paddle.png',
                self::GATEWAY_CASH => 'https://example.com/images/gateways/cash.png',
                self::GATEWAY_CHEQUE => 'https://example.com/images/gateways/cheque.png',
                self::GATEWAY_BANK_TRANSFER => 'https://example.com/images/gateways/bank-transfer.png',
            ];

            return $defaultLogos[$this->code] ?? null;
        }

        return asset('storage/'.$this->logo);
    }

    /**
     * Scope a query to only include active gateways.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include online payment gateways.
     */
    public function scopeOnline($query)
    {
        return $query->where('is_online', true);
    }

    /**
     * Scope a query to only include gateways of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the configuration as an array.
     */
    public function getConfig(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'is_active' => $this->is_active,
            'is_online' => $this->is_online,
            'has_api' => $this->has_api,
            'test_mode' => $this->test_mode,
            'logo_url' => $this->logo_url,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'currency' => $this->currency,
            'supported_currencies' => $this->supported_currencies,
            'fee_percentage' => $this->fee_percentage,
            'fee_fixed' => $this->fee_fixed,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'extra_attributes' => $this->extra_attributes,
            'is_configured' => $this->is_configured,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get the API configuration for the gateway.
     */
    public function getApiConfig(): array
    {
        if (! $this->is_online) {
            return [];
        }

        $config = [
            'test_mode' => $this->test_mode,
            'api_key' => $this->api_key,
            'api_secret' => $this->api_secret,
            'api_username' => $this->api_username,
            'api_password' => $this->api_password,
            'callback_url' => $this->callback_url,
            'webhook_url' => $this->webhook_url,
            'success_url' => $this->success_url,
            'cancel_url' => $this->cancel_url,
            'ipn_url' => $this->ipn_url,
            'currency' => $this->currency,
        ];

        // Add gateway-specific configurations
        $config = array_merge($config, $this->extra_attributes ?? []);

        return $config;
    }
}
