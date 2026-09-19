<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Services\ActivityLog;
use App\Services\LicenseManager;

class LicenseController extends Controller
{
    public function __construct()
    {
        Auth::requireRole('admin');
    }

    public function index(): void
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $params = [];
        $where = 'l.deleted_at IS NULL';
        if ($q !== '') {
            $where .= ' AND (l.license_key LIKE ? OR c.name LIKE ? OR c.email LIKE ?)';
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
            $params[] = "%{$q}%";
        }

        $rows = Database::getInstance()->fetchAll(
            "SELECT l.*, c.name AS customer_name, c.email AS customer_email, p.name AS plan_name
             FROM licenses l
             LEFT JOIN customers c ON l.customer_id = c.id
             LEFT JOIN plans p ON l.plan_id = p.id
             WHERE {$where}
             ORDER BY l.id DESC",
            $params
        );

        $this->view('admin.licenses', ['admin' => Auth::user(), 'licenses' => $rows, 'q' => $q]);
    }

    public function show(int $id): void
    {
        $db = Database::getInstance();
        $license = $db->fetch(
            "SELECT l.*, c.name AS customer_name, c.email AS customer_email, p.name AS plan_name, p.price AS plan_price
             FROM licenses l
             LEFT JOIN customers c ON l.customer_id = c.id
             LEFT JOIN plans p ON l.plan_id = p.id
             WHERE l.id = ? AND l.deleted_at IS NULL",
            [$id]
        );

        if (!$license) {
            $this->withError('License not found.');
            $this->redirect('/admin/licenses');
        }

        $manager = new LicenseManager();
        $activations = $manager->activations($id);

        $this->view('admin.license-detail', [
            'admin'       => Auth::user(),
            'license'     => $license,
            'activations' => $activations,
            'activeCount' => $manager->activeActivationCount($id),
        ]);
    }

    public function create(): void
    {
        $customers = Database::getInstance()->fetchAll("SELECT id, name, email FROM customers WHERE deleted_at IS NULL ORDER BY name ASC");
        $plans = Database::getInstance()->fetchAll("SELECT id, product, name, price FROM plans WHERE active = 1 ORDER BY product ASC");

        $this->view('admin.license-form', [
            'admin'     => Auth::user(),
            'customers' => $customers,
            'plans'     => $plans,
        ]);
    }

    public function store(): void
    {
        $data = $this->validate([
            'plan_id' => 'required|numeric',
        ]);

        $db = Database::getInstance();
        $plan = $db->fetch("SELECT * FROM plans WHERE id = ?", [(int) $data['plan_id']]);
        if (!$plan) {
            $this->withError('Plan not found.');
            $this->redirect('/admin/licenses/create');
        }

        $customerId = (int) ($_POST['customer_id'] ?? 0);
        $newEmail = strtolower(trim((string) ($_POST['new_customer_email'] ?? '')));

        if ($customerId <= 0 && $newEmail === '') {
            $this->withError('Select a customer or enter a new customer email.');
            $this->redirect('/admin/licenses/create');
        }
        if ($newEmail !== '' && !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->withError('The new customer email is not valid.');
            $this->redirect('/admin/licenses/create');
        }

        if ($customerId > 0) {
            $customer = $db->fetch("SELECT * FROM customers WHERE id = ? AND deleted_at IS NULL", [$customerId]);
            if (!$customer) {
                $this->withError('Customer not found.');
                $this->redirect('/admin/licenses/create');
            }
        } else {
            $customer = $db->fetch("SELECT * FROM customers WHERE email = ? AND deleted_at IS NULL", [$newEmail]);
            if (!$customer) {
                $customerId = (int) $db->insert('customers', [
                    'name'       => ucwords(str_replace(['.', '_', '+'], ' ', strstr($newEmail, '@', true) ?: $newEmail)),
                    'email'      => $newEmail,
                    'password'   => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT),
                    'role'       => 'customer',
                    'status'     => 'active',
                    'locale'     => 'en',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $customer = $db->fetch("SELECT * FROM customers WHERE id = ?", [$customerId]);
                ActivityLog::log('admin.created_customer', 'admin', (int) Auth::id(), [
                    'customer_id' => $customerId,
                    'email'       => $newEmail,
                ]);
            } else {
                $customerId = (int) $customer['id'];
            }
        }

        $addon = (string) ($_POST['addon'] ?? '');
        $addon = in_array($addon, ['deployment', 'care', 'deployment_care'], true) ? $addon : '';

        $licenseKey = $_POST['license_key'] ?? '';
        $issued = (new LicenseManager())->issue(
            $customerId,
            (int) $plan['id'],
            (string) $plan['product'],
            [
                'license_key' => $licenseKey !== '' ? strtoupper($licenseKey) : null,
                'metadata'    => array_filter(['addons' => $addon]),
            ]
        );

        ActivityLog::log('admin.issued_license', 'admin', (int) Auth::id(), [
            'customer_id' => $customerId,
            'license_key' => $issued['license']['license_key'],
            'addons'      => $addon,
        ]);

        $this->withSuccess('License issued: ' . $issued['license']['license_key']);
        $this->redirect('/admin/licenses/' . $issued['license']['id']);
    }

    public function updateStatus(int $id): void
    {
        $data = $this->validate(['status' => 'required|in:active,suspended,cancelled']);
        $db = Database::getInstance();

        $license = $db->fetch("SELECT * FROM licenses WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$license) {
            $this->withError('License not found.');
            $this->redirect('/admin/licenses');
        }

        $db->update('licenses', [
            'status'     => $data['status'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.updated_license_status', 'admin', (int) Auth::id(), [
            'license_id' => $id,
            'status'     => $data['status'],
        ]);

        $this->withSuccess('License marked as ' . $data['status'] . '.');
        $this->redirect('/admin/licenses/' . $id);
    }

    public function extend(int $id): void
    {
        $data = $this->validate(['days' => 'required|numeric']);

        $days = max(1, (int) $data['days']);
        $db = Database::getInstance();
        $license = $db->fetch("SELECT * FROM licenses WHERE id = ? AND deleted_at IS NULL", [$id]);
        if (!$license) {
            $this->withError('License not found.');
            $this->redirect('/admin/licenses');
        }

        $base = $license['expires_at'] && strtotime($license['expires_at']) > time()
            ? $license['expires_at']
            : date('Y-m-d H:i:s');
        $newExpiry = date('Y-m-d H:i:s', strtotime("+{$days} days", strtotime($base)));

        $db->update('licenses', [
            'expires_at' => $newExpiry,
            'status'     => 'active',
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        ActivityLog::log('admin.extended_license', 'admin', (int) Auth::id(), [
            'license_id' => $id,
            'days'       => $days,
            'expires_at' => $newExpiry,
        ]);

        $this->withSuccess('License extended by ' . $days . ' day(s) to ' . $newExpiry . '.');
        $this->redirect('/admin/licenses/' . $id);
    }
}
