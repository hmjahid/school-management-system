<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$_ENV['APP_NAME'] = 'Eskoofy Test';
$_ENV['APP_URL'] = 'http://website.local';
$_ENV['APP_TIMEZONE'] = 'UTC';
$_ENV['APP_LOCALE'] = 'en';
$_ENV['APP_DEBUG'] = 'true';
$_ENV['ESKOOFY_VARIANT'] = 'int';
$_ENV['AUTH_TABLE'] = 'customers';

// Licensing (international standard)
$_ENV['LICENSE_CURRENCY'] = 'USD';
$_ENV['LICENSE_KEY_PREFIX'] = 'ESK';
$_ENV['LICENSE_KEY_CHUNKS'] = '4';
$_ENV['LICENSE_KEY_LENGTH'] = '4';
$_ENV['LICENSE_MAX_ACTIVATIONS'] = '3';
$_ENV['LICENSE_ACTIVITY_LOG'] = 'false'; // keep unit tests DB-free

// Payments — manual works today; Paddle stubbed.
$_ENV['GATEWAY_DEFAULT'] = 'manual';
$_ENV['GATEWAY_CURRENCY'] = 'USD';

$_ENV['PADDLE_TEST_MODE'] = 'true';
$_ENV['PADDLE_VENDOR_ID'] = '1';
$_ENV['PADDLE_VENDOR_AUTH_CODE'] = 'fake_auth_code';

date_default_timezone_set('UTC');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}