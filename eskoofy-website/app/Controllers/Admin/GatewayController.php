<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
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
        ]],
        'rocket' => ['Rocket', 'local', [
            'username' => ['Username', false], 'password' => ['Password', true],
            'api_key' => ['API key', true], 'test_mode' => ['Test mode (1/0)', false],
        ]],
        'nagad'  => ['Nagad', 'local', [
            'merchant_id' => ['Merchant ID', false], 'api_key' => ['API key', true],
            'api_secret' => ['API secret', true], 'test_mode' => ['Test mode (1/0)', false],
        ]],
        'stripe' => ['Stripe', 'international', [
            'secret_key' => ['Secret key', true], 'publishable_key' => ['Publishable key', false],
            'webhook_secret' => ['Webhook secret', true], 'test_mode' => ['Test mode (1/0)', false],
        ]],
        'paypal' => ['PayPal', 'international', [
            'client_id' => ['Client ID', false], 'client_secret' => ['Client secret', true],
            'webhook_secret' => ['Webhook secret', true], 'test_mode' => ['Test mode (1/0)', false],
        ]],
        'paddle' => ['Paddle', 'international', [
            'vendor_id' => ['Vendor ID', false], 'vendor_auth_code' => ['Vendor auth code', true],
            'webhook_secret' => ['Webhook secret', true], 'test_mode' => ['Test mode (1/0)', false],
        ]],
    ];

    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $settings = Settings::all();
        $this->view('admin.gateways', [
            'admin'     => Auth::user(),
            'settings'  => $settings,
            'gateways'  => self::GATEWAYS,
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