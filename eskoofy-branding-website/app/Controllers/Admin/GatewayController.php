<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Gateways\GatewayFactory;
use App\Models\Settings;
use App\Services\ActivityLog;

class GatewayController extends Controller
{
    /** code => [label, group, fields => [field => [label, secret?]]] */
    private const GATEWAYS = [
        'manual' => ['Manual / Bank Transfer', 'offline', []],
        'bkash'  => ['bKash', 'local', [
            'app_key' => ['App key', false], 'app_secret' => ['App secret', true],
            'username' => ['Username', false], 'password' => ['Password', true],
            'test_mode' => ['Test mode (1/0)', false],
            'live_url' => ['Live base URL', false],
        ]],
        'rocket' => ['Rocket', 'local', [
            'username' => ['Username', false], 'password' => ['Password', true],
            'api_key' => ['API key', true], 'test_mode' => ['Test mode (1/0)', false],
            'live_url' => ['Live base URL', false],
        ]],
        'nagad'  => ['Nagad', 'local', [
            'merchant_id' => ['Merchant ID', false], 'api_key' => ['API key', true],
            'api_secret' => ['API secret', true], 'test_mode' => ['Test mode (1/0)', false],
            'live_url' => ['Live base URL', false],
        ]],
        'uddoktapay' => ['UddoktaPay', 'local', [
            'api_key' => ['API key', true], 'test_mode' => ['Test mode (1/0)', false],
            'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        // Extended BD hosted gateway set — config-driven (GenericHostedGateway).
        'shurjopay' => ['ShurjoPay', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'portwallet' => ['PortWallet', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'cellfin' => ['Cellfin', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'purse' => ['Purse', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'cashby' => ['Cashby', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'upay' => ['UPay', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'mycash' => ['MyCash', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'payer' => ['Payer', 'local', [
            'api_key' => ['API key', true], 'merchant_id' => ['Merchant ID', false],
            'test_mode' => ['Test mode (1/0)', false], 'sandbox_url' => ['Sandbox base URL', false], 'live_url' => ['Live base URL', false],
        ]],
        'test_gateway' => ['Test / Sandbox', 'testing', []],
        'stripe' => ['Stripe', 'international', [
            'secret_key' => ['Secret key', true], 'publishable_key' => ['Publishable key', false],
            'webhook_secret' => ['Webhook secret', true], 'test_mode' => ['Test mode (1/0)', false],
            'live_url' => ['Live base URL', false],
        ]],
        'paypal' => ['PayPal', 'international', [
            'client_id' => ['Client ID', false], 'client_secret' => ['Client secret', true],
            'webhook_secret' => ['Webhook secret', true], 'test_mode' => ['Test mode (1/0)', false],
            'live_url' => ['Live base URL', false],
        ]],
        'paddle' => ['Paddle', 'international', [
            'vendor_id' => ['Vendor ID', false], 'vendor_auth_code' => ['Vendor auth code', true],
            'webhook_secret' => ['Webhook secret', true], 'test_mode' => ['Test mode (1/0)', false],
            'live_url' => ['Live base URL', false],
        ]],
    ];

    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $settings = Settings::all();

        $diagnostics = [];
        foreach (self::GATEWAYS as $code => $meta) {
            $enabled = (string) ($settings['gateway.' . $code . '.enabled'] ?? ($code === 'manual' || $code === 'test_gateway' ? '1' : '0')) === '1';
            $configured = $code === 'manual';
            $driver = '—';
            try {
                $gateway = GatewayFactory::make($code);
                $configured = method_exists($gateway, 'isConfigured') ? (bool) $gateway->isConfigured() : true;
                $driver = (new \ReflectionClass($gateway))->getShortName();
            } catch (\Throwable) {
            }
            $diagnostics[$code] = [
                'label'      => $meta[0],
                'group'      => $meta[1],
                'enabled'    => $enabled,
                'test_mode'  => (string) ($settings['gateway.' . $code . '.test_mode'] ?? ''),
                'configured' => $configured,
                'driver'     => $driver,
                'testable'   => $code === 'test_gateway',
            ];
        }

        $this->view('admin.gateways', [
            'admin'       => Auth::user(),
            'settings'    => $settings,
            'gateways'    => self::GATEWAYS,
            'diagnostics' => $diagnostics,
            'testEnabled' => GatewayFactory::testGatewayEnabled(),
        ]);
    }

    public function update(): void
    {
        $pairs = [];
        foreach (self::GATEWAYS as $code => $meta) {
            $pairs['gateway.' . $code . '.enabled'] = isset($_POST['enabled'][$code]) ? '1' : '0';
            foreach ($meta[2] as $field => $_info) {
                $key = 'gateway.' . $code . '.' . $field;
                $pairs[$key] = trim((string) ($_POST['fields'][$code][$field] ?? ''));
            }
        }
        Settings::setMany($pairs);

        ActivityLog::log('gateways.updated', 'admin', (int) Auth::id());

        $this->withSuccess('Payment gateway settings saved.');
        $this->redirect('/admin/gateways');
    }
}