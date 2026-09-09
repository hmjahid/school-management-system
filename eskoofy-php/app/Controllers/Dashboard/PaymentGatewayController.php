<?php
declare(strict_types=1);

namespace App\Controllers\Dashboard;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;

class PaymentGatewayController extends Controller
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(): void
    {
        Auth::requireAuth();
        $rows = $this->db->fetchAll(
            "SELECT * FROM payment_gateways ORDER BY sort_order ASC, name ASC"
        );

        $this->view('dashboard.payment-gateways.index', ['rows' => $rows]);
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
            'name'           => 'required|max:255',
            'is_active'      => 'numeric',
            'sort_order'     => 'numeric',
            'merchant_id'    => 'max:255',
            'api_key'        => 'max:255',
            'api_secret'     => 'max:255',
            'sandbox'        => 'numeric',
            'fee_percentage' => 'numeric',
            'fee_fixed'      => 'numeric',
            'min_amount'     => 'numeric',
            'max_amount'     => 'numeric',
        ]);

        $updateData = [
            'name'           => $data['name'],
            'is_active'      => $data['is_active'] ?? $gateway['is_active'],
            'sort_order'     => $data['sort_order'] ?? $gateway['sort_order'],
            'merchant_id'    => $data['merchant_id'] ?? null,
            'api_key'        => $data['api_key'] ?? null,
            'api_secret'     => $data['api_secret'] ?? null,
            'sandbox'        => $data['sandbox'] ?? 0,
            'fee_percentage' => $data['fee_percentage'] ?? 0,
            'fee_fixed'      => $data['fee_fixed'] ?? 0,
            'min_amount'     => $data['min_amount'] ?? null,
            'max_amount'     => $data['max_amount'] ?? null,
            'updated_at'     => date('Y-m-d H:i:s'),
        ];

        $this->db->update('payment_gateways', $updateData, 'id = ?', [$id]);

        Session::getInstance()->flash('success', 'Gateway updated successfully.');
        $this->redirect('/dashboard/payment-gateways');
    }
}
