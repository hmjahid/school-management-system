<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\DatabaseInterface;
use App\Core\Session;

class PaymentGatewayController extends Controller
{
    private DatabaseInterface $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();

        // Ensure the optional UddoktaPay gateway row exists so admins can enable
        // it and store credentials. Disabled by default.
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

        $rows = $this->db->fetchAll(
            "SELECT * FROM payment_gateways WHERE deleted_at IS NULL ORDER BY sort_order ASC, name ASC"
        );

        $this->view('dashboard.payment-gateways.index', [
            'rows'     => $rows,
            'gateways' => $rows,
        ]);
    }

    public function update(int $id): void
    {
        Auth::requireAuth();

        $gateway = $this->db->fetch("SELECT * FROM payment_gateways WHERE id = ? LIMIT 1", [$id]);
        if (!$gateway) {
            Session::getInstance()->flash('error', 'Gateway not found.');
            $this->redirect('/dashboard/payment-gateways');

            return;
        }

        $data = $this->validate([
            'name'       => 'required|max:255',
            'sort_order' => 'numeric',
        ]);

        $this->db->update('payment_gateways', [
            'name'       => $data['name'],
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => $data['sort_order'] ?? $gateway['sort_order'],
            'api_key'    => trim((string) ($_POST['api_key'] ?? '')),
            'api_secret' => trim((string) ($_POST['api_secret'] ?? '')),
            'test_mode'  => isset($_POST['test_mode']) ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Gateway updated successfully.');
        $this->redirect('/dashboard/payment-gateways');
    }
}
