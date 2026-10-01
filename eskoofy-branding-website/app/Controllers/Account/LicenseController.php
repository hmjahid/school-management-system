<?php
declare(strict_types=1);

namespace App\Controllers\Account;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;
use App\Services\LicenseManager;

class LicenseController extends Controller
{
    private int $customerId;

    public function __construct()
    {
        Auth::requireAuth();
        $this->customerId = (int) Auth::id();
    }

    public function index(): void
    {
        $rows = Database::getInstance()->fetchAll(
            "SELECT l.*, p.name AS plan_name, p.period AS plan_period
             FROM licenses l
             LEFT JOIN plans p ON l.plan_id = p.id
             WHERE l.customer_id = ? AND l.deleted_at IS NULL
             ORDER BY l.created_at DESC",
            [$this->customerId]
        );

        $this->view('account.licenses', [
            'customer'    => Auth::user(),
            'licenses'    => $rows,
            'accountPage' => 'licenses',
        ]);
    }

    public function show(int $id): void
    {
        $db = Database::getInstance();
        $license = $db->fetch(
            "SELECT l.*, p.name AS plan_name, p.period AS plan_period, p.description AS plan_description, p.price AS plan_price
             FROM licenses l
             LEFT JOIN plans p ON l.plan_id = p.id
             WHERE l.id = ? AND l.customer_id = ? AND l.deleted_at IS NULL",
            [$id, $this->customerId]
        );

        if (!$license) {
            $this->withError('License not found.');
            $this->redirect('/account/licenses');
        }

        $manager = new LicenseManager();
        $activations = $manager->activations((int) $license['id']);

        $subscription = $db->fetch(
            "SELECT * FROM subscriptions WHERE license_id = ? ORDER BY (status = 'active') DESC, id DESC LIMIT 1",
            [(int) $license['id']]
        );

        $customer = Auth::user();
        $gateways = \App\Gateways\GatewayFactory::gatewaysForCountry(
            isset($customer['country']) && $customer['country'] !== '' ? (string) $customer['country'] : null
        );
        $isBd = \App\Gateways\GatewayFactory::isBdCountry(
            isset($customer['country']) && $customer['country'] !== '' ? (string) $customer['country'] : null
        );

        $this->view('account.license-detail', [
            'customer'    => $customer,
            'license'     => $license,
            'activations' => $activations,
            'activeCount' => $manager->activeActivationCount((int) $license['id']),
            'expired'     => $manager->isExpired($license),
            'subscription' => $subscription,
            'gateways'    => $gateways,
            'is_bd'       => $isBd,
            'accountPage' => 'licenses',
        ]);
    }

    public function revoke(): void
    {
        $data = $this->validate(['activation_id' => 'required|numeric']);
        $db = Database::getInstance();

        $activation = $db->fetch(
            "SELECT la.* FROM license_activations la
             JOIN licenses l ON la.license_id = l.id
             WHERE la.id = ? AND l.customer_id = ?",
            [(int) $data['activation_id'], $this->customerId]
        );

        if (!$activation) {
            $this->withError('Activation not found.');
            $this->redirect('/account/licenses');
        }

        $db->update('license_activations', [
            'deactivated_at' => date('Y-m-d H:i:s'),
            'updated_at'     => date('Y-m-d H:i:s'),
        ], 'id = ?', [(int) $activation['id']]);

        ActivityLog::log('account.revoked_activation', 'customer', $this->customerId, [
            'activation_id' => (int) $activation['id'],
        ]);

        $this->withSuccess('Activation revoked.');
        $this->redirect('/account/licenses/' . $activation['license_id']);
    }

    /**
     * Customer self-serve subscription management. Auto-renewal is not charged
     * automatically on this site (renewals are always a new payment), so
     * "cancel" simply marks the subscription cancelled at the end of the paid
     * period, and "resume" puts it back. The license expiry is never changed.
     */
    public function subscription(string $action, int $id): void
    {
        $allowed = ['cancel', 'resume'];
        if (!in_array($action, $allowed, true)) {
            $this->withError('Invalid action.');
            $this->redirect('/account/licenses');
        }

        $db = Database::getInstance();
        $row = $db->fetch(
            "SELECT s.* FROM subscriptions s
             JOIN licenses l ON l.id = s.license_id
             WHERE s.id = ? AND l.customer_id = ?",
            [$id, $this->customerId]
        );

        if (!$row) {
            $this->withError('Subscription not found.');
            $this->redirect('/account/licenses');
        }

        $status = $action === 'cancel' ? 'cancelled' : 'active';

        $db->update('subscriptions', [
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('account.subscription_' . $action, 'customer', $this->customerId, [
            'subscription_id' => $id,
            'license_id'      => (int) ($row['license_id'] ?? 0),
        ]);

        $this->withSuccess(
            $action === 'cancel'
                ? 'Subscription cancelled. Your license stays valid until ' . (($row['current_period_end'] ?? 'the end of the period')) . '.'
                : 'Subscription resumed.'
        );
        $this->redirect('/account/licenses/' . (int) $row['license_id']);
    }
}
