<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$_ENV['APP_TIMEZONE'] = 'UTC';
$_ENV['APP_URL'] = 'http://localhost:8000';
$_ENV['APP_LOCALE'] = 'en';
$_ENV['APP_NAME'] = 'Eskoofy Test';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['ESKOOFY_VARIANT'] = 'bd';
$_ENV['PAYMENT_CURRENCY'] = 'BDT';
$_ENV['PAYMENT_DEFAULT_GATEWAY'] = 'bkash';
$_ENV['BKASH_TEST_MODE'] = 'true';
$_ENV['BKASH_SANDBOX_URL'] = 'https://sandbox.bkash.com';
$_ENV['ROCKET_TEST_MODE'] = 'true';
$_ENV['ROCKET_SANDBOX_URL'] = 'https://sandbox.rocket.com';
$_ENV['NAGAD_TEST_MODE'] = 'true';
$_ENV['NAGAD_SANDBOX_URL'] = 'https://sandbox.nagad.com';
$_ENV['STRIPE_TEST_MODE'] = 'true';
$_ENV['STRIPE_SECRET_KEY'] = 'sk_test_fake';
$_ENV['STRIPE_PUBLISHABLE_KEY'] = 'pk_test_fake';
$_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_test_fake';
$_ENV['PAYPAL_TEST_MODE'] = 'true';
$_ENV['PAYPAL_CLIENT_ID'] = 'fake_client_id';
$_ENV['PAYPAL_CLIENT_SECRET'] = 'fake_client_secret';
$_ENV['PAYPAL_MODE'] = 'sandbox';
$_ENV['PADDLE_TEST_MODE'] = 'true';
$_ENV['PADDLE_VENDOR_ID'] = '1';
$_ENV['PADDLE_VENDOR_AUTH_CODE'] = 'fake_auth_code';

$_ENV['SCHOOL_NAME'] = 'Test School';
$_ENV['SCHOOL_TAGLINE'] = 'Test Tagline';
$_ENV['OFFLINE_BANK_NAME'] = 'Test Bank';
$_ENV['OFFLINE_ACCOUNT_NAME'] = 'Test Account';
$_ENV['OFFLINE_ACCOUNT_NUMBER'] = '1234567890';
$_ENV['OFFLINE_ROUTING_NUMBER'] = '000123456';
$_ENV['OFFLINE_INSTRUCTIONS'] = 'Transfer to the account above.';

date_default_timezone_set('UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
