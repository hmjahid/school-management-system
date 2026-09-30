<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;
use App\Models\PaymentGateway;

class PaymentGatewayController extends Controller
{
    private DatabaseInterface $db;

    /**
     * International gateways shipped disabled by default. An admin enables any
     * of them (or adds their own) from this screen.
     *
     * @var array<string, array{name: string, currency: string, currencies: list<string>, sort: int}>
     */
    private const INTERNATIONAL_GATEWAYS = [
        'gpay'          => ['name' => 'Google Pay',    'currency' => 'USD', 'currencies' => ['USD', 'EUR', 'GBP', 'INR', 'SGD', 'AUD', 'CAD'], 'sort' => 20],
        'applepay'      => ['name' => 'Apple Pay',     'currency' => 'USD', 'currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD'], 'sort' => 21],
        'razorpay'      => ['name' => 'Razorpay',      'currency' => 'INR', 'currencies' => ['INR', 'USD'], 'sort' => 22],
        'paystack'      => ['name' => 'Paystack',      'currency' => 'NGN', 'currencies' => ['NGN', 'GHS', 'ZAR', 'KES', 'USD'], 'sort' => 23],
        'flutterwave'   => ['name' => 'Flutterwave',   'currency' => 'NGN', 'currencies' => ['NGN', 'GHS', 'KES', 'ZAR', 'USD', 'EUR', 'GBP'], 'sort' => 24],
        'sslcommerz'    => ['name' => 'SSLCommerz',    'currency' => 'BDT', 'currencies' => ['BDT', 'USD', 'EUR', 'GBP', 'INR'], 'sort' => 25],
        'square'        => ['name' => 'Square',        'currency' => 'USD', 'currencies' => ['USD', 'CAD', 'GBP', 'AUD', 'JPY', 'EUR'], 'sort' => 26],
        'mollie'        => ['name' => 'Mollie',        'currency' => 'EUR', 'currencies' => ['EUR', 'USD', 'GBP', 'CHF', 'SEK', 'NOK', 'DKK', 'PLN'], 'sort' => 27],
        'authorize_net' => ['name' => 'Authorize.Net', 'currency' => 'USD', 'currencies' => ['USD', 'CAD', 'GBP', 'EUR', 'AUD'], 'sort' => 28],
        'xendit'        => ['name' => 'Xendit',        'currency' => 'IDR', 'currencies' => ['IDR', 'PHP', 'THB', 'VND', 'MYR', 'SGD', 'USD'], 'sort' => 29],
        'adyen'         => ['name' => 'Adyen',         'currency' => 'EUR', 'currencies' => ['EUR', 'USD', 'GBP', 'AUD', 'CAD', 'JPY', 'SGD', 'INR'], 'sort' => 30],
        'skrill'        => ['name' => 'Skrill',        'currency' => 'USD', 'currencies' => ['USD', 'EUR', 'GBP', 'AUD', 'CAD', 'JPY'], 'sort' => 31],
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $this->ensureGateways();

        $rows = $this->db->fetchAll(
            "SELECT * FROM payment_gateways WHERE deleted_at IS NULL ORDER BY sort_order ASC, name ASC"
        );

        $rows = PaymentGateway::hydrate($rows);

        $this->view('dashboard.payment-gateways.index', [
            'rows'     => $rows,
            // Legacy fallback view contract.
            'gateways' => $rows,
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();

        $this->view('dashboard.payment-gateways.create', [
            'gateway'     => new PaymentGateway([
                'type'     => 'online_payment',
                'currency' => 'USD',
                'is_online' => 1,
                'has_api'   => 1,
                'test_mode' => 1,
            ]),
            'types'       => $this->types(),
            'action'      => '/dashboard/payment-gateways',
            'method'      => 'post',
            'submitLabel' => __('Create gateway'),
        ]);
    }

    public function store(): void
    {
        Auth::requireAuth();

        $data = $this->payload();

        if ($data['name'] === '' || $data['code'] === '') {
            Session::getInstance()->flash('error', 'Name and code are required.');
            $this->redirect('/dashboard/payment-gateways/create');
            return;
        }

        if ($this->db->fetch("SELECT id FROM payment_gateways WHERE code = ? LIMIT 1", [$data['code']])) {
            Session::getInstance()->flash('error', 'A gateway with that code already exists.');
            $this->redirect('/dashboard/payment-gateways/create');
            return;
        }

        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->insert('payment_gateways', $data);

        Session::getInstance()->flash('success', 'Payment gateway created.');
        $this->redirect('/dashboard/payment-gateways');
    }

    public function edit(int $id): void
    {
        Auth::requireAuth();

        $gateway = PaymentGateway::find($id);
        if (!$gateway) {
            Session::getInstance()->flash('error', 'Gateway not found.');
            $this->redirect('/dashboard/payment-gateways');
            return;
        }

        $this->view('dashboard.payment-gateways.edit', [
            'gateway'     => $gateway,
            'types'       => $this->types(),
            'action'      => '/dashboard/payment-gateways/' . $id,
            'method'      => 'put',
            'submitLabel' => __('Update gateway'),
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();

        $gateway = PaymentGateway::find($id);
        if (!$gateway) {
            Session::getInstance()->flash('error', 'Gateway not found.');
            $this->redirect('/dashboard/payment-gateways');
            return;
        }

        $data = $this->payload();

        if ($data['name'] === '' || $data['code'] === '') {
            Session::getInstance()->flash('error', 'Name and code are required.');
            $this->redirect('/dashboard/payment-gateways/' . $id . '/edit');
            return;
        }

        $existing = $this->db->fetch(
            "SELECT id FROM payment_gateways WHERE code = ? AND id <> ? LIMIT 1",
            [$data['code'], $id]
        );
        if ($existing) {
            Session::getInstance()->flash('error', 'A gateway with that code already exists.');
            $this->redirect('/dashboard/payment-gateways/' . $id . '/edit');
            return;
        }

        // Keep existing secrets when the form submits them blank.
        foreach (['api_key', 'api_secret', 'api_username', 'api_password'] as $secret) {
            if ($data[$secret] === '' && !empty($gateway->{$secret})) {
                unset($data[$secret]);
            }
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->update('payment_gateways', $data, 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Payment gateway updated.');
        $this->redirect('/dashboard/payment-gateways');
    }

    public function destroy(int $id): void
    {
        Auth::requireAuth();

        $this->db->update('payment_gateways', [
            'deleted_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Payment gateway deleted.');
        $this->redirect('/dashboard/payment-gateways');
    }

    /**
     * @return array<string, string>
     */
    private function types(): array
    {
        return [
            'online_payment'           => __('Online payment'),
            'mobile_financial_service' => __('Mobile financial service'),
            'bank'                     => __('Bank'),
            'other'                    => __('Other'),
        ];
    }

    /**
     * Build the persisted gateway row from the submitted form.
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $post = static fn (string $key): string => trim((string) ($_POST[$key] ?? ''));

        $code = strtolower(preg_replace('/[^A-Za-z0-9_]/', '_', $post('code')) ?? '');
        $currency = strtoupper($post('currency')) ?: 'USD';

        $currencies = array_values(array_filter(array_map(
            static fn ($value) => strtoupper(trim($value)),
            preg_split('/[\s,]+/', $post('supported_currencies')) ?: []
        )));
        if ($currencies === []) {
            $currencies = [$currency];
        }

        $keys = (array) ($_POST['extra_keys'] ?? []);
        $values = (array) ($_POST['extra_values'] ?? []);
        $extra = [];
        foreach ($keys as $index => $key) {
            $key = trim((string) $key);
            if ($key !== '') {
                $extra[$key] = (string) ($values[$index] ?? '');
            }
        }

        $numberOrNull = static function (string $value): ?float {
            return $value === '' ? null : (float) $value;
        };

        $type = $post('type');
        if (!isset($this->types()[$type])) {
            $type = 'online_payment';
        }

        return [
            'name'                 => $post('name'),
            'code'                 => $code,
            'type'                 => $type,
            'is_active'            => isset($_POST['is_active']) ? 1 : 0,
            'is_online'            => isset($_POST['is_online']) ? 1 : 0,
            'has_api'              => isset($_POST['has_api']) ? 1 : 0,
            'test_mode'            => isset($_POST['test_mode']) ? 1 : 0,
            'sandbox_url'          => $post('sandbox_url') ?: null,
            'live_url'             => $post('live_url') ?: null,
            'api_key'              => $post('api_key'),
            'api_secret'           => $post('api_secret'),
            'api_username'         => $post('api_username'),
            'api_password'         => $post('api_password'),
            'callback_url'         => $post('callback_url') ?: null,
            'webhook_url'          => $post('webhook_url') ?: null,
            'success_url'          => $post('success_url') ?: null,
            'cancel_url'           => $post('cancel_url') ?: null,
            'ipn_url'              => $post('ipn_url') ?: null,
            'logo'                 => $post('logo') ?: null,
            'description'          => $post('description') ?: null,
            'instructions'         => $post('instructions') ?: null,
            'currency'             => $currency,
            'fee_percentage'       => (float) ($post('fee_percentage') !== '' ? $post('fee_percentage') : 0),
            'fee_fixed'            => (float) ($post('fee_fixed') !== '' ? $post('fee_fixed') : 0),
            'min_amount'           => $numberOrNull($post('min_amount')),
            'max_amount'           => $numberOrNull($post('max_amount')),
            'sort_order'           => (int) ($post('sort_order') !== '' ? $post('sort_order') : 0),
            'supported_currencies' => json_encode($currencies),
            'extra_attributes'     => json_encode($extra),
        ];
    }

    private function ensureGateways(): void
    {
        // Optional BD aggregator.
        if ($this->db->count('payment_gateways', 'code = ?', ['uddoktapay']) === 0) {
            $this->db->insert('payment_gateways', [
                'name'        => 'UddoktaPay',
                'code'        => 'uddoktapay',
                'type'        => 'mobile_financial_service',
                'is_active'   => 0,
                'is_online'   => 1,
                'has_api'     => 1,
                'test_mode'   => 1,
                'sandbox_url' => 'https://sandbox.uddoktapay.com/api',
                'live_url'    => 'https://pay.uddoktapay.com/api',
                'currency'    => 'BDT',
                'sort_order'  => 4,
                'created_at'  => date('Y-m-d H:i:s'),
                'updated_at'  => date('Y-m-d H:i:s'),
            ]);
        }

        foreach (self::INTERNATIONAL_GATEWAYS as $code => $meta) {
            if ($this->db->count('payment_gateways', 'code = ?', [$code]) > 0) {
                continue;
            }

            $this->db->insert('payment_gateways', [
                'name'                 => $meta['name'],
                'code'                 => $code,
                'type'                 => 'online_payment',
                'is_active'            => 0,
                'is_online'            => 1,
                'has_api'              => 1,
                'test_mode'            => 1,
                'callback_url'         => '/api/payments/' . $code . '/callback',
                'webhook_url'          => '/api/payments/' . $code . '/webhook',
                'description'          => $meta['name'] . ' — international, hosted checkout.',
                'currency'             => $meta['currency'],
                'supported_currencies' => json_encode($meta['currencies']),
                'extra_attributes'     => json_encode([]),
                'sort_order'           => $meta['sort'],
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);
        }
    }
}
