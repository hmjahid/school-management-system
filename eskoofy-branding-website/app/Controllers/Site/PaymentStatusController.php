<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Gateways\GatewayFactory;
use App\Services\LicenseManager;

/**
 * Handles gateway return/callback after a hosted checkout. Verifies the
 * transaction, marks the payment paid, issues the license + subscription,
 * and shows a result page.
 */
class PaymentStatusController extends Controller
{
    public function show(string $reference): void
    {
        $db = Database::getInstance();
        $payment = $db->fetch(
            "SELECT * FROM payments WHERE reference = ? ORDER BY id DESC LIMIT 1",
            [$reference]
        );

        if (!$payment) {
            $this->withError('Payment not found.');
            $this->redirect('/pricing');
        }

        // Only the paying customer can view the result.
        if (!Auth::check() || (int) $payment['customer_id'] !== (int) Auth::id()) {
            $this->redirect('/login?redirect=/checkout/status/' . $reference);
        }

        $cancelled = ($_GET['status'] ?? '') === 'cancelled';
        $pending = ($_GET['status'] ?? '') === 'pending' || ($payment['status'] ?? '') === 'pending';

        // If not already paid, verify with the gateway (idempotent).
        // Manual/bank-transfer payments stay pending until an admin approves them.
        if (($payment['status'] ?? '') !== 'paid' && !$cancelled && !$pending) {
            $gatewayService = GatewayFactory::make((string) $payment['gateway']);
            $result = $gatewayService->verify($payment, $_GET);
            if (($result['success'] ?? false)) {
                $payment = $db->fetch("SELECT * FROM payments WHERE id = ?", [(int) $payment['id']]);
            }
        }

        $paid = ($payment['status'] ?? '') === 'paid';

        if ($paid && empty($payment['license_id'])) {
            $plan = $db->fetch("SELECT * FROM plans WHERE id = ?", [(int) $payment['plan_id']]);
            $manager = new LicenseManager();
            if ($plan) {
                $issued = $manager->issue(
                    (int) $payment['customer_id'],
                    (int) $plan['id'],
                    (string) $plan['product'],
                    ['payment' => (int) $payment['id'], 'metadata' => ['gateway' => $payment['gateway']]]
                );
                $licenseId = (int) $issued['license']['id'];
                $manager->createSubscription($licenseId, (int) $plan['id'], (string) $payment['gateway'], [
                    'customer_id' => (int) $payment['customer_id'],
                ]);
                $this->withSuccess('Payment verified. Your subscription is active.');
                $this->redirect('/account/licenses/' . $licenseId);
            }
        }

        $this->view('site.payment-status', [
            'payment'   => $payment,
            'paid'      => $paid,
            'cancelled' => $cancelled,
            'pending'   => $pending,
        ]);
    }
}