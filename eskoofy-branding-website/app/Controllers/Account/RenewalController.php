<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Gateways\GatewayFactory;
use App\Services\LicenseManager;

class RenewalController extends Controller
{
    public function __construct()
    {
        Auth::requireAuth();
    }

    public function renew(int $id): void
    {
        $db = Database::getInstance();
        $license = $db->fetch(
            "SELECT * FROM licenses WHERE id = ? AND customer_id = ? AND deleted_at IS NULL",
            [$id, (int) Auth::id()]
        );

        if (!$license) {
            $this->withError('License not found.');
            $this->redirect('/account/licenses');
        }

        $customer = Auth::user();
        $planId = (int) ($_POST['plan_id'] ?? $license['plan_id']);
        $gateway = (string) ($_POST['gateway'] ?? '');

        $allowed = GatewayFactory::gatewaysForCountry(
            isset($customer['country']) && $customer['country'] !== '' ? (string) $customer['country'] : null
        );
        if (!in_array($gateway, $allowed, true)) {
            $this->withError('Selected payment method is not available for your region.');
            $this->redirect('/account/licenses/' . $id);
        }

        $plan = $db->fetch("SELECT * FROM plans WHERE id = ? AND active = 1", [$planId]);
        if (!$plan) {
            $this->withError('Plan not found.');
            $this->redirect('/account/licenses/' . $id);
        }

        $isBd = GatewayFactory::isBdCountry(
            isset($customer['country']) && $customer['country'] !== '' ? (string) $customer['country'] : null
        );
        $usd      = (float) $plan['price'];
        $currency = $isBd ? 'BDT' : 'USD';
        $amount   = $isBd ? GatewayFactory::toBdt($usd) : $usd;
        $reference = 'REN-' . strtoupper(bin2hex(random_bytes(4)));

        $paymentId = $db->insert('payments', [
            'customer_id' => (int) $customer['id'],
            'license_id'  => $id,
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

        $payment = $db->fetch("SELECT * FROM payments WHERE id = ?", [$paymentId]);
        $gatewayService = GatewayFactory::make($gateway);
        $returnUrl = (string) ($_ENV['APP_URL'] ?? 'http://localhost:8001') . '/checkout/status/' . $reference;

        $result = $gatewayService->process([
            'plan'      => $plan,
            'customer'  => $customer,
            'amount'    => $amount,
            'currency'  => $currency,
            'reference' => $reference,
            'return_url' => $returnUrl,
            'cancel_url' => $returnUrl . '?status=cancelled',
        ], $payment);

        if (($result['success'] ?? false) && !empty($result['redirect_url'])) {
            if (!empty($result['transaction_id'])) {
                $db->update('payments', [
                    'transaction_id' => $result['transaction_id'],
                    'updated_at'     => date('Y-m-d H:i:s'),
                ], 'id = ?', [$paymentId]);
            }
            $this->redirect((string) $result['redirect_url']);
        }

        if (($result['success'] ?? false)) {
            $manager = new LicenseManager();
            $manager->createSubscription($id, $planId, $gateway, ['customer_id' => (int) $customer['id']]);
            $this->withSuccess('Payment received. Your subscription has been extended.');
            $this->redirect('/account/licenses/' . $id);
        }

        $this->withError($result['message'] ?? 'Renewal failed.');
        $this->redirect('/account/licenses/' . $id);
    }
}