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

        $this->view('site.checkout', [
            'plan'     => $plan,
            'customer' => Auth::user(),
            'gateway'  => GatewayFactory::defaultCode(),
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
        $gateway = GatewayFactory::defaultCode();

        $paymentId = Database::getInstance()->insert('payments', [
            'customer_id' => (int) $customer['id'],
            'plan_id'     => $planId,
            'gateway'     => $gateway,
            'reference'   => 'ORD-' . strtoupper(bin2hex(random_bytes(4))),
            'amount'      => $plan['price'],
            'currency'    => $plan['currency'] ?? 'USD',
            'status'      => 'pending',
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        $payment = Database::getInstance()->fetch("SELECT * FROM payments WHERE id = ?", [$paymentId]);
        $gatewayService = GatewayFactory::make($gateway);
        $result = $gatewayService->process(['plan' => $plan, 'customer' => $customer], $payment);

        $manager = new LicenseManager();
        $issued = $manager->issue(
            (int) $customer['id'],
            $planId,
            (string) $plan['product'],
            ['payment' => $paymentId, 'metadata' => ['gateway' => $gateway]]
        );

        $this->withSuccess('Payment received. Your license key has been issued.');
        $this->redirect('/account/licenses/' . $issued['license']['id']);
    }
}
