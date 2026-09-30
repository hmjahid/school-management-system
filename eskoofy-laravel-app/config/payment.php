<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Credentials and endpoint configuration for each supported gateway.
    | In production these values MUST come from environment variables or
    | a secure secrets manager — never commit real credentials to source.
    |
    */

    'gateways' => [
        'bkash' => [
            'api_username' => env('BKASH_API_USERNAME'),
            'api_password' => env('BKASH_API_PASSWORD'),
            'api_key' => env('BKASH_API_KEY'),
            'api_secret' => env('BKASH_API_SECRET'),
            'sandbox_url' => env('BKASH_SANDBOX_URL', 'https://checkout.sandbox.bka.sh'),
            'live_url' => env('BKASH_LIVE_URL', 'https://checkout.bka.sh'),
            'webhook_secret' => env('BKASH_WEBHOOK_SECRET'),
        ],

        'nagad' => [
            'api_username' => env('NAGAD_API_USERNAME'),
            'api_password' => env('NAGAD_API_PASSWORD'),
            'api_key' => env('NAGAD_API_KEY'),
            'api_secret' => env('NAGAD_API_SECRET'),
            'sandbox_url' => env('NAGAD_SANDBOX_URL', 'https://sandbox.mynagad.com'),
            'live_url' => env('NAGAD_LIVE_URL', 'https://api.mynagad.com/api'),
            'webhook_secret' => env('NAGAD_WEBHOOK_SECRET'),
        ],

        'rocket' => [
            'api_username' => env('ROCKET_API_USERNAME'),
            'api_password' => env('ROCKET_API_PASSWORD'),
            'api_key' => env('ROCKET_API_KEY'),
            'api_secret' => env('ROCKET_API_SECRET'),
            'sandbox_url' => env('ROCKET_SANDBOX_URL', 'https://api.sandbox.rocket.com.bd/api/v1'),
            'live_url' => env('ROCKET_LIVE_URL', 'https://api.rocket.com.bd/api/v1'),
            'webhook_secret' => env('ROCKET_WEBHOOK_SECRET'),
        ],

        'uddoktapay' => [
            'api_key' => env('UDDOKTAPAY_API_KEY'),
            'sandbox_url' => env('UDDOKTAPAY_SANDBOX_URL', 'https://sandbox.uddoktapay.com/api'),
            'live_url' => env('UDDOKTAPAY_LIVE_URL', 'https://pay.uddoktapay.com/api'),
            'webhook_secret' => env('UDDOKTAPAY_WEBHOOK_SECRET', env('UDDOKTAPAY_API_KEY')),
            'currency' => 'BDT',
        ],

        'stripe' => [
            'api_key' => env('STRIPE_API_KEY'),
            'api_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'api_username' => env('STRIPE_PUBLISHABLE_KEY'),
            'sandbox_url' => env('STRIPE_SANDBOX_URL', 'https://api.stripe.com/v1'),
            'live_url' => env('STRIPE_LIVE_URL', 'https://api.stripe.com/v1'),
            'currency' => env('STRIPE_CURRENCY', 'USD'),
        ],

        'paypal' => [
            'api_key' => env('PAYPAL_CLIENT_ID'),
            'api_secret' => env('PAYPAL_CLIENT_SECRET'),
            'sandbox_url' => env('PAYPAL_SANDBOX_URL', 'https://api-m.sandbox.paypal.com'),
            'live_url' => env('PAYPAL_LIVE_URL', 'https://api-m.paypal.com'),
            'currency' => env('PAYPAL_CURRENCY', 'USD'),
            'webhook_secret' => env('PAYPAL_WEBHOOK_SECRET'),
        ],

        'paddle' => [
            'api_key' => env('PADDLE_VENDOR_ID'),
            'api_secret' => env('PADDLE_VENDOR_AUTH_CODE'),
            'api_username' => env('PADDLE_PRODUCT_ID'),
            'sandbox_url' => env('PADDLE_SANDBOX_URL', 'https://sandbox-checkout.paddle.com/api/1.0/orders?product='),
            'live_url' => env('PADDLE_LIVE_URL', 'https://checkout.paddle.com/api/1.0/orders?product='),
            'currency' => env('PADDLE_CURRENCY', 'USD'),
            'paddle_public_key' => env('PADDLE_PUBLIC_KEY'),
        ],

        /*
        |------------------------------------------------------------------
        | International gateways
        |------------------------------------------------------------------
        |
        | These ship disabled by default: an install only offers them once an
        | admin enables them (Dashboard > Payment Gateways). They are served by
        | the config-driven GenericHostedGatewayAdapter, so the checkout /
        | verify / refund endpoints live on the payment_gateways row (or here
        | as fallbacks) — never as per-vendor code.
        |
        | The sandbox/live URLs below are the vendors' well-known API bases and
        | are only defaults; the hosted checkout endpoint must be supplied per
        | install where the vendor issues a per-merchant URL.
        |
        */
        'gpay' => [
            'api_key' => env('GPAY_API_KEY'),
            'api_secret' => env('GPAY_API_SECRET'),
            'sandbox_url' => env('GPAY_SANDBOX_URL'),
            'live_url' => env('GPAY_LIVE_URL'),
            'currency' => env('GPAY_CURRENCY', 'USD'),
            'supported_currencies' => ['USD', 'EUR', 'GBP', 'INR', 'SGD', 'AUD', 'CAD'],
        ],

        'applepay' => [
            'api_key' => env('APPLEPAY_API_KEY'),
            'api_secret' => env('APPLEPAY_API_SECRET'),
            'sandbox_url' => env('APPLEPAY_SANDBOX_URL'),
            'live_url' => env('APPLEPAY_LIVE_URL'),
            'currency' => env('APPLEPAY_CURRENCY', 'USD'),
            'supported_currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD'],
        ],

        'razorpay' => [
            'api_key' => env('RAZORPAY_KEY_ID'),
            'api_secret' => env('RAZORPAY_KEY_SECRET'),
            'sandbox_url' => env('RAZORPAY_SANDBOX_URL', 'https://api.razorpay.com/v1'),
            'live_url' => env('RAZORPAY_LIVE_URL', 'https://api.razorpay.com/v1'),
            'currency' => env('RAZORPAY_CURRENCY', 'INR'),
            'supported_currencies' => ['INR', 'USD'],
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        ],

        'paystack' => [
            'api_key' => env('PAYSTACK_SECRET_KEY'),
            'api_secret' => env('PAYSTACK_PUBLIC_KEY'),
            'sandbox_url' => env('PAYSTACK_SANDBOX_URL', 'https://api.paystack.co'),
            'live_url' => env('PAYSTACK_LIVE_URL', 'https://api.paystack.co'),
            'currency' => env('PAYSTACK_CURRENCY', 'NGN'),
            'supported_currencies' => ['NGN', 'GHS', 'ZAR', 'KES', 'USD'],
            'webhook_secret' => env('PAYSTACK_WEBHOOK_SECRET'),
        ],

        'flutterwave' => [
            'api_key' => env('FLUTTERWAVE_PUBLIC_KEY'),
            'api_secret' => env('FLUTTERWAVE_SECRET_KEY'),
            'sandbox_url' => env('FLUTTERWAVE_SANDBOX_URL', 'https://api.flutterwave.com/v3'),
            'live_url' => env('FLUTTERWAVE_LIVE_URL', 'https://api.flutterwave.com/v3'),
            'currency' => env('FLUTTERWAVE_CURRENCY', 'NGN'),
            'supported_currencies' => ['NGN', 'GHS', 'KES', 'ZAR', 'USD', 'EUR', 'GBP'],
            'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET'),
        ],

        'sslcommerz' => [
            'api_key' => env('SSLCOMMERZ_STORE_ID'),
            'api_secret' => env('SSLCOMMERZ_STORE_PASSWORD'),
            'sandbox_url' => env('SSLCOMMERZ_SANDBOX_URL', 'https://sandbox.sslcommerz.com/gwprocess/v4/api.php'),
            'live_url' => env('SSLCOMMERZ_LIVE_URL', 'https://securepay.sslcommerz.com/gwprocess/v4/api.php'),
            'currency' => env('SSLCOMMERZ_CURRENCY', 'BDT'),
            'supported_currencies' => ['BDT', 'USD', 'EUR', 'GBP', 'INR'],
        ],

        'square' => [
            'api_key' => env('SQUARE_ACCESS_TOKEN'),
            'api_secret' => env('SQUARE_APPLICATION_ID'),
            'sandbox_url' => env('SQUARE_SANDBOX_URL', 'https://connect.squareupsandbox.com'),
            'live_url' => env('SQUARE_LIVE_URL', 'https://connect.squareup.com'),
            'currency' => env('SQUARE_CURRENCY', 'USD'),
            'supported_currencies' => ['USD', 'CAD', 'GBP', 'AUD', 'JPY', 'EUR'],
        ],

        'mollie' => [
            'api_key' => env('MOLLIE_API_KEY'),
            'sandbox_url' => env('MOLLIE_SANDBOX_URL', 'https://api.mollie.com/v2'),
            'live_url' => env('MOLLIE_LIVE_URL', 'https://api.mollie.com/v2'),
            'currency' => env('MOLLIE_CURRENCY', 'EUR'),
            'supported_currencies' => ['EUR', 'USD', 'GBP', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN'],
            'webhook_secret' => env('MOLLIE_WEBHOOK_SECRET'),
        ],

        'authorize_net' => [
            'api_key' => env('AUTHORIZE_NET_API_LOGIN_ID'),
            'api_secret' => env('AUTHORIZE_NET_TRANSACTION_KEY'),
            'sandbox_url' => env('AUTHORIZE_NET_SANDBOX_URL', 'https://apitest.authorize.net/xml/v1/request.api'),
            'live_url' => env('AUTHORIZE_NET_LIVE_URL', 'https://api.authorize.net/xml/v1/request.api'),
            'currency' => env('AUTHORIZE_NET_CURRENCY', 'USD'),
            'supported_currencies' => ['USD', 'CAD', 'GBP', 'EUR', 'AUD'],
            'signature_key' => env('AUTHORIZE_NET_SIGNATURE_KEY'),
        ],

        'xendit' => [
            'api_key' => env('XENDIT_SECRET_KEY'),
            'sandbox_url' => env('XENDIT_SANDBOX_URL', 'https://api.xendit.co'),
            'live_url' => env('XENDIT_LIVE_URL', 'https://api.xendit.co'),
            'currency' => env('XENDIT_CURRENCY', 'IDR'),
            'supported_currencies' => ['IDR', 'PHP', 'THB', 'VND', 'MYR', 'SGD', 'USD'],
            'webhook_secret' => env('XENDIT_WEBHOOK_TOKEN'),
        ],

        'adyen' => [
            'api_key' => env('ADYEN_API_KEY'),
            'api_secret' => env('ADYEN_MERCHANT_ACCOUNT'),
            'sandbox_url' => env('ADYEN_SANDBOX_URL', 'https://checkout-test.adyen.com/v71'),
            'live_url' => env('ADYEN_LIVE_URL', 'https://checkout-live.adyen.com/v71'),
            'currency' => env('ADYEN_CURRENCY', 'EUR'),
            'supported_currencies' => ['EUR', 'USD', 'GBP', 'AUD', 'CAD', 'JPY', 'SGD', 'INR'],
            'webhook_secret' => env('ADYEN_HMAC_KEY'),
        ],

        'skrill' => [
            'api_key' => env('SKRILL_MERCHANT_EMAIL'),
            'api_secret' => env('SKRILL_SECRET_WORD'),
            'sandbox_url' => env('SKRILL_SANDBOX_URL', 'https://pay.sandbox.skrill.com/app/pay.pl'),
            'live_url' => env('SKRILL_LIVE_URL', 'https://pay.skrill.com/app/pay.pl'),
            'currency' => env('SKRILL_CURRENCY', 'USD'),
            'supported_currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY'],
            'webhook_secret' => env('SKRILL_WEBHOOK_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    */

    'currency' => env('PAYMENT_CURRENCY', 'BDT'),

    /*
    |--------------------------------------------------------------------------
    | Offline / Bank Transfer Settings
    |--------------------------------------------------------------------------
    |
    | Displayed on receipts and the payment page when an offline gateway
    | (cash/bank transfer) is selected.
    |
    */

    'offline' => [
        'account_name' => env('OFFLINE_ACCOUNT_NAME', env('APP_NAME', 'School').' Account'),
        'account_number' => env('OFFLINE_ACCOUNT_NUMBER'),
        'bank_name' => env('OFFLINE_BANK_NAME'),
        'branch' => env('OFFLINE_BRANCH_NAME'),
        'routing_number' => env('OFFLINE_ROUTING_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refund Settings
    |--------------------------------------------------------------------------
    */

    'refund' => [
        // Whether to process refunds asynchronously via the queue.
        'queue' => env('PAYMENT_REFUND_QUEUE', false),
    ],
];
