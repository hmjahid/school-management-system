<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Gateways\GatewayFactory;
use App\Services\LicenseManager;

class CheckoutController extends Controller
{
    private function countryOfCustomer(array $customer): ?string
    {
        return isset($customer['country']) && $customer['country'] !== ''
            ? (string) $customer['country']
            : null;
    }

    public function index(): void
    {
        $planId = (int) ($_GET['plan'] ?? 0);
        $plan = Database::getInstance()->fetch("SELECT * FROM plans WHERE id = ? AND active = 1", [$planId]);

        if (!$plan) {
            $this->withError('Plan not found.');
            $this->redirect('/pricing');
        }

        if (!Auth::check()) {
            Session::getInstance()->flash('info', 'Please login or register to complete your purchase.');
            $this->redirect('/login?redirect=/checkout?plan=' . $planId);
        }

        $customer = Auth::user();
        $country  = $this->countryOfCustomer($customer);
        $isBd     = GatewayFactory::isBdCountry($country);
        $gateways = GatewayFactory::gatewaysForCountry($country);
        $usd      = (float) $plan['price'];
        $display  = $isBd ? GatewayFactory::toBdt($usd) : $usd;
        $currency = $isBd ? 'BDT' : 'USD';

        $this->view('site.checkout', [
            'plan'      => $plan,
            'customer'  => $customer,
            'gateways'  => $gateways,
            'is_bd'     => $isBd,
            'display'   => $display,
            'currency'  => $currency,
            'usd'       => $usd,
        ]);
    }

    public function process(): void
    {
        $data = $this->validate(['plan_id' => 'required|numeric']);
        $planId = (int) $data['plan_id'];
        $plan = Database::getInstance()->fetch("SELECT * FROM plans WHERE id = ? AND active = 1", [$planId]);

        if (!$plan) {
            $this->error('Plan not found.', 404);
        }
        if (!Auth::check()) {
            $this->error('Please login first.', 401);
        }

        $customer = Auth::user();
        $country  = $this->countryOfCustomer($customer);
        $isBd     = GatewayFactory::isBdCountry($country);
        $gateway  = (string) ($_POST['gateway'] ?? '');

        $allowed = GatewayFactory::gatewaysForCountry($country);
        if (!in_array($gateway, $allowed, true)) {
            $this->error('Selected payment method is not available for your region.', 400);
        }

        $usd       = (float) $plan['price'];
        $currency  = $isBd ? 'BDT' : 'USD';
        $amount    = $isBd ? GatewayFactory::toBdt($usd) : $usd;
        $reference = 'ORD-' . strtoupper(bin2hex(random_bytes(4)));

        $paymentId = Database::getInstance()->insert('payments', [
            'customer_id' => (int) $customer['id'],
            'plan_id'     => $planId,
            'gateway'     => $gateway,
            'reference'   => $reference,
            'amount'      => $amount,
            'currency'    => $currency,
            'variant'     => $isBd ? 'bd' : 'int',
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $payment = Database::getInstance()->fetch("SELECT * FROM payments WHERE id = ?", [$paymentId]);
        $gatewayService = GatewayFactory::make($gateway);

        $returnUrl = (string) ($_ENV['APP_URL'] ?? 'http://localhost:8001') . '/checkout/status/' . $reference;
        $order = [
            'plan'      => $plan,
            'customer'  => $customer,
            'amount'    => $amount,
            'currency'  => $currency,
            'reference' => $reference,
            'return_url' => $returnUrl,
            'cancel_url' => $returnUrl . '?status=cancelled',
        ];

        $result = $gatewayService->process($order, $payment);

        // Online gateways redirect to their hosted checkout.
        if (($result['success'] ?? false) && !empty($result['redirect_url'])) {
            // Record the gateway transaction id (payment intent / order id) on
            // the pending payment so the callback can verify it.
            if (!empty($result['transaction_id'])) {
                Database::getInstance()->update('payments', [
                    'transaction_id' => $result['transaction_id'],
                    'updated_at'     => date('Y-m-d H:i:s'),
                ], 'id = ?', [$paymentId]);
            }
            $this->redirect((string) $result['redirect_url']);
        }

        // Manual / bank transfer payments: record as pending until an admin
        // confirms the transfer, then send the license + package.
        if (($result['status'] ?? '') === 'pending') {
            $this->withSuccess($result['message'] ?? 'Your payment is awaiting confirmation.');
            $this->redirect('/checkout/status/' . $reference . '?status=pending');
        }

        // Immediate/offline gateways: mark paid and issue.
        if (($result['success'] ?? false)) {
            $manager = new LicenseManager();
            $issued = $manager->issue(
                (int) $customer['id'],
                $planId,
                (string) $plan['product'],
                ['payment' => $paymentId, 'metadata' => ['gateway' => $gateway]]
            );
            $licenseId = (int) $issued['license']['id'];
            $manager->createSubscription($licenseId, $planId, $gateway, ['customer_id' => (int) $customer['id']]);

            $this->withSuccess('Payment received. Your subscription is active and your license key has been issued.');
            $this->redirect('/account/licenses/' . $licenseId);
        }

        $this->withError($result['message'] ?? 'Payment failed. Please try again.');
        $this->redirect('/checkout?plan=' . $planId);
    }

    /**
     * Local sandbox page for the zero-credential test gateway. Shows the
     * payment summary and three simulate buttons — no external host involved.
     */
    public function sandbox(string $reference): void
    {
        $payment = $this->ownedTestPayment($reference);

        $plan = Database::getInstance()->fetch("SELECT * FROM plans WHERE id = ?", [(int) $payment['plan_id']]);

        $this->view('site.sandbox', [
            'payment' => $payment,
            'plan'    => $plan,
        ]);
    }

    /**
     * Apply a simulated sandbox result to a test payment.
     */
    public function simulate(string $reference): void
    {
        $payment = $this->ownedTestPayment($reference);

        $simulate = strtolower((string) ($_POST['simulate'] ?? ''));
        if (!in_array($simulate, ['success', 'failure', 'cancel'], true)) {
            $this->error('Invalid sandbox simulation.', 422);
        }

        GatewayFactory::make('test_gateway')->verify($payment, ['simulate' => $simulate]);

        if ($simulate === 'success') {
            $this->withSuccess('Test payment completed.');
            $this->redirect('/checkout/status/' . rawurlencode($reference));
        }

        $this->withError('Test payment simulated as ' . ($simulate === 'cancel' ? 'cancelled' : 'failed') . '.');
        $this->redirect('/checkout?plan=' . (int) $payment['plan_id']);
    }

    /**
     * Fetch a test payment, enforcing ownership and the test-gateway code.
     *
     * @return array<string, mixed>
     */
    private function ownedTestPayment(string $reference): array
    {
        $db = Database::getInstance();
        $payment = $db->fetch("SELECT * FROM payments WHERE reference = ? ORDER BY id DESC LIMIT 1", [$reference]);

        if (!$payment) {
            $this->withError('Payment not found.');
            $this->redirect('/pricing');
        }

        if (!Auth::check() || (int) $payment['customer_id'] !== (int) Auth::id()) {
            $this->redirect('/login?redirect=/checkout/sandbox/' . rawurlencode($reference));
        }

        if ((string) $payment['gateway'] !== 'test_gateway') {
            $this->withError('That payment is not a test payment.');
            $this->redirect('/checkout/status/' . rawurlencode($reference));
        }

        return $payment;
    }
}