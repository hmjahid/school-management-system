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
            'customer' => Auth::user(),
            'licenses' => $rows,
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

        $this->view('account.license-detail', [
            'customer'    => Auth::user(),
            'license'     => $license,
            'activations' => $activations,
            'activeCount' => $manager->activeActivationCount((int) $license['id']),
            'expired'     => $manager->isExpired($license),
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
}
